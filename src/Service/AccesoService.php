<?php
// src/Service/AccesoService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\AccesoGrupo;
use Educarama\Model\AccesoSeccion;
use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Seccion;
use Educarama\Model\Usuario;
use Educarama\Repository\AccesoGrupoRepository;
use Educarama\Repository\AccesoSeccionRepository;
use Educarama\Repository\GrupoRepository;
use Educarama\Repository\RolRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\UsuarioRepository;
use Educarama\Repository\Vista\UsuarioConAcceso;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Vista\AccesoEnPantalla;
use Educarama\Service\Vista\AccesosEnPantalla;
use PDO;

/**
 * Gestión de roles: quién tiene acceso a un grupo o a una sección, y con qué rol.
 *
 * Reglas (CLAUDE.md, "Accesos"):
 *  - Se suma a alguien buscando su cuenta YA registrada por email. Si no hay
 *    cuenta, se le pide que se registre (las invitaciones por email, más adelante).
 *  - Jefe de grupo: agrega, cambia y quita accesos del grupo y de cualquier
 *    sección del grupo, con cualquier rol.
 *  - Jefe de sección: solo cambia el rol de los usuarios ya asignados a su
 *    sección, hasta Responsable. No agrega, no quita y no toca a otro Jefe.
 *  - El último Jefe de un grupo no se puede quitar ni bajar de rol (ni él mismo).
 *  - Ver la lista: la del grupo, el Jefe de grupo; la de una sección, su Jefe
 *    (y el Jefe de grupo, que es Jefe de todas sus secciones).
 *  - Sin ningún acceso al grupo o a la sección: 404 (no se confirma que
 *    existen). Con acceso pero sin ser Jefe: 403.
 */
final class AccesoService extends ServicioBase
{
    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly AccesoGrupoRepository $accesosGrupo,
        private readonly AccesoSeccionRepository $accesosSeccion,
        private readonly UsuarioRepository $usuarios,
        private readonly GrupoRepository $grupos,
        private readonly SeccionRepository $secciones,
        private readonly RolRepository $roles
    ) {
        parent::__construct($conexion);
    }

    // =================================================================
    // Grupo (todo, solo el Jefe de grupo)
    // =================================================================

    /** @return UsuarioConAcceso[] Primero los de mayor rol. */
    public function listarAccesosGrupo(int $idUsuario, int $idGrupo): array
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);

        return $this->accesosGrupo->listarUsuariosPorGrupo($idGrupo);
    }

    /**
     * La lista del grupo con lo que se puede hacer en cada fila (solo el Jefe
     * de grupo). Al último Jefe no se le ofrece cambiar el rol ni quitarlo:
     * el Service lo rechazaría.
     */
    public function pantallaGrupo(int $idUsuario, int $idGrupo): AccesosEnPantalla
    {
        $lista = $this->listarAccesosGrupo($idUsuario, $idGrupo);
        $jefes = count(array_filter($lista, fn (UsuarioConAcceso $u) => $u->nivel === NivelRol::Jefe));

        $filas = array_map(function (UsuarioConAcceso $u) use ($jefes): AccesoEnPantalla {
            $ultimoJefe = $u->nivel === NivelRol::Jefe && $jefes <= 1;

            return new AccesoEnPantalla($u, $ultimoJefe ? [] : NivelRol::cases(), !$ultimoJefe);
        }, $lista);

        return new AccesosEnPantalla($filas, NivelRol::cases());
    }

    /**
     * Da acceso al grupo a una cuenta ya registrada, buscándola por email.
     *
     * @throws ReglaNegocioException si no hay cuenta con ese email o ya tiene acceso
     */
    public function darAccesoGrupo(int $idUsuario, int $idGrupo, string $email, NivelRol $nivel): void
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);
        $destino = $this->cuentaPorEmail($email);

        if ($this->accesosGrupo->buscar((int) $destino->id, $idGrupo) !== null) {
            throw new ReglaNegocioException("{$destino->nombre} ya tiene acceso al grupo: cambiá su rol desde la lista.");
        }

        $this->accesosGrupo->insertar(new AccesoGrupo((int) $destino->id, $idGrupo, $this->idRol($nivel)));
    }

    /** Cambia el rol de grupo. No deja al grupo sin Jefe. */
    public function cambiarRolGrupo(int $idUsuario, int $idGrupo, int $idDestino, NivelRol $nivel): void
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);
        $acceso = $this->accesosGrupo->buscar($idDestino, $idGrupo)
            ?? throw new NoEncontradoException('Ese usuario no tiene acceso al grupo.');

        if ($nivel !== NivelRol::Jefe) {
            $this->exigirQueNoSeaElUltimoJefe($idGrupo, $acceso);
        }

        $acceso->idRol = $this->idRol($nivel);
        $this->accesosGrupo->actualizar($acceso);
    }

    /**
     * Quita el acceso al grupo. Sus filas de sección (si tenía) quedan: son
     * accesos aparte y se quitan desde cada sección.
     */
    public function quitarAccesoGrupo(int $idUsuario, int $idGrupo, int $idDestino): void
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);
        $acceso = $this->accesosGrupo->buscar($idDestino, $idGrupo)
            ?? throw new NoEncontradoException('Ese usuario no tiene acceso al grupo.');

        $this->exigirQueNoSeaElUltimoJefe($idGrupo, $acceso);

        $this->accesosGrupo->eliminar($idDestino, $idGrupo);
    }

    // =================================================================
    // Sección
    // =================================================================

    /**
     * @return UsuarioConAcceso[] Los asignados a la sección (sin los Jefes de
     *         grupo, que son Jefes por derivación y se ven en la lista del grupo).
     */
    public function listarAccesosSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Jefe, 'La sección no existe.');

        return $this->accesosSeccion->listarUsuariosPorSeccion($idSeccion);
    }

    /**
     * La lista de la sección con lo que se puede hacer en cada fila.
     *  - Jefe de grupo: todo, con cualquier rol.
     *  - Jefe de sección: solo cambiar el rol, hasta Responsable, y no el de otro
     *    Jefe (tampoco el suyo); no agrega ni quita.
     */
    public function pantallaSeccion(int $idUsuario, int $idSeccion): AccesosEnPantalla
    {
        $lista = $this->listarAccesosSeccion($idUsuario, $idSeccion);
        $esJefeDeGrupo = $this->permisos->esJefeDeGrupo($idUsuario, $this->seccion($idSeccion)->idGrupo);
        $hastaResponsable = [NivelRol::Supervisor, NivelRol::Ayudante, NivelRol::Responsable];

        $filas = array_map(fn (UsuarioConAcceso $u) => new AccesoEnPantalla(
            $u,
            $esJefeDeGrupo ? NivelRol::cases() : ($u->nivel === NivelRol::Jefe ? [] : $hastaResponsable),
            $esJefeDeGrupo,
        ), $lista);

        return new AccesosEnPantalla($filas, $esJefeDeGrupo ? NivelRol::cases() : []);
    }

    /** Asigna una cuenta ya registrada a la sección (solo el Jefe de grupo). */
    public function darAccesoSeccion(int $idUsuario, int $idSeccion, string $email, NivelRol $nivel): void
    {
        $seccion = $this->seccion($idSeccion);
        $this->exigirJefeDeGrupo($idUsuario, $seccion->idGrupo);
        $destino = $this->cuentaPorEmail($email);

        if ($this->accesosSeccion->buscar((int) $destino->id, $idSeccion) !== null) {
            throw new ReglaNegocioException("{$destino->nombre} ya está asignado a la sección: cambiá su rol desde la lista.");
        }

        $this->accesosSeccion->insertar(new AccesoSeccion((int) $destino->id, $idSeccion, $this->idRol($nivel)));
    }

    /**
     * Cambia el rol de alguien ya asignado a la sección.
     *  - Jefe de grupo: cualquier rol.
     *  - Jefe de sección: hasta Responsable, y no a alguien que ya es Jefe
     *    (nombrar o bajar Jefes de sección lo hace el Jefe de grupo).
     */
    public function cambiarRolSeccion(int $idUsuario, int $idSeccion, int $idDestino, NivelRol $nivel): void
    {
        $seccion = $this->seccion($idSeccion);
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Jefe, 'La sección no existe.');

        $acceso = $this->accesosSeccion->buscar($idDestino, $idSeccion)
            ?? throw new NoEncontradoException('Ese usuario no está asignado a la sección.');

        if (!$this->permisos->esJefeDeGrupo($idUsuario, $seccion->idGrupo)) {
            if ($nivel->value > NivelRol::Responsable->value) {
                throw new AccesoDenegadoException('Solo el Jefe de grupo puede nombrar a un Jefe de sección.');
            }
            if ($this->nivelDeRol($acceso->idRol) === NivelRol::Jefe) {
                throw new AccesoDenegadoException('Solo el Jefe de grupo puede cambiar el rol de un Jefe de sección.');
            }
        }

        $acceso->idRol = $this->idRol($nivel);
        $this->accesosSeccion->actualizar($acceso);
    }

    /** Quita a alguien de la sección (solo el Jefe de grupo). */
    public function quitarAccesoSeccion(int $idUsuario, int $idSeccion, int $idDestino): void
    {
        $seccion = $this->seccion($idSeccion);
        $this->exigirJefeDeGrupo($idUsuario, $seccion->idGrupo);

        if ($this->accesosSeccion->buscar($idDestino, $idSeccion) === null) {
            throw new NoEncontradoException('Ese usuario no está asignado a la sección.');
        }

        $this->accesosSeccion->eliminar($idDestino, $idSeccion);
    }

    // =================================================================
    // Vista de solo lectura: miembros por sección de un grupo
    // =================================================================

    /**
     * Los accesos de cada sección del grupo, para el desplegable de solo lectura
     * en la pantalla de roles del grupo. Solo el Jefe de grupo (que hereda Jefe
     * en todas las secciones) puede verlo.
     *
     * @return list<array{seccion: Seccion, accesos: UsuarioConAcceso[]}>
     */
    public function accesosPorSeccionDeGrupo(int $idUsuario, int $idGrupo): array
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);

        $resultado = [];
        foreach ($this->secciones->listarPorGrupo($idGrupo) as $seccion) {
            $resultado[] = [
                'seccion' => $seccion,
                'accesos' => $this->accesosSeccion->listarUsuariosPorSeccion((int) $seccion->id),
            ];
        }

        return $resultado;
    }

    // =================================================================
    // Auxiliares
    // =================================================================

    /**
     * @throws NoEncontradoException si el grupo no existe o el usuario no tiene ningún acceso a él
     * @throws AccesoDenegadoException si tiene acceso pero no es su Jefe
     */
    private function exigirJefeDeGrupo(int $idUsuario, int $idGrupo): void
    {
        if ($this->grupos->buscarPorId($idGrupo) === null || !$this->permisos->tieneAccesoAlGrupo($idUsuario, $idGrupo)) {
            throw new NoEncontradoException('El grupo no existe.');
        }

        $this->permisos->exigirEnGrupo($idUsuario, $idGrupo, NivelRol::Jefe);
    }

    /** Si $acceso es de un Jefe y es el único Jefe del grupo, corta. */
    private function exigirQueNoSeaElUltimoJefe(int $idGrupo, AccesoGrupo $acceso): void
    {
        if ($this->nivelDeRol($acceso->idRol) !== NivelRol::Jefe) {
            return;
        }

        $jefes = array_filter(
            $this->accesosGrupo->listarPorGrupo($idGrupo),
            fn (AccesoGrupo $a) => $this->nivelDeRol($a->idRol) === NivelRol::Jefe,
        );

        if (count($jefes) <= 1) {
            throw new ReglaNegocioException('Es el único Jefe del grupo: primero nombrá a otro Jefe.');
        }
    }

    /**
     * La cuenta registrada con ese email. Si no hay, se le pide que se registre
     * (más adelante: invitarla por email).
     */
    private function cuentaPorEmail(string $email): Usuario
    {
        $email = Validacion::emailOpcional($email)
            ?? throw new ReglaNegocioException('Escribí el email de la cuenta.');

        return $this->usuarios->buscarPorEmail(mb_strtolower($email))
            ?? throw new ReglaNegocioException('No hay una cuenta con ese email: pedile que se registre en Educarama.');
    }

    private function seccion(int $idSeccion): Seccion
    {
        return $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');
    }

    private function idRol(NivelRol $nivel): int
    {
        $rol = $this->roles->buscarPorNivel($nivel)
            ?? throw new \LogicException("Falta el rol de nivel {$nivel->value} en la tabla rol.");

        return (int) $rol->id;
    }

    private function nivelDeRol(int $idRol): NivelRol
    {
        $rol = $this->roles->buscarPorId($idRol)
            ?? throw new \LogicException("El rol $idRol no existe en la tabla rol.");

        return NivelRol::from($rol->nivel);
    }
}
