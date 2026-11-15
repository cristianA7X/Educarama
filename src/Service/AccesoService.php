<?php // src/Service/AccesoService.php
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

    public function listarAccesosGrupo(int $idUsuario, int $idGrupo): array
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);
        return $this->accesosGrupo->listarUsuariosPorGrupo($idGrupo);
    }

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

    public function darAccesoGrupo(int $idUsuario, int $idGrupo, string $email, NivelRol $nivel): void
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);
        $destino = $this->cuentaPorEmail($email);
        if ($this->accesosGrupo->buscar((int) $destino->id, $idGrupo) !== null) {
            throw new ReglaNegocioException("{$destino->nombre} ya tiene acceso al grupo: cambiá su rol desde la lista.");
        }
        $this->accesosGrupo->insertar(new AccesoGrupo((int) $destino->id, $idGrupo, $this->idRol($nivel)));
    }

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

    public function quitarAccesoGrupo(int $idUsuario, int $idGrupo, int $idDestino): void
    {
        $this->exigirJefeDeGrupo($idUsuario, $idGrupo);
        $acceso = $this->accesosGrupo->buscar($idDestino, $idGrupo)
            ?? throw new NoEncontradoException('Ese usuario no tiene acceso al grupo.');
        $this->exigirQueNoSeaElUltimoJefe($idGrupo, $acceso);
        $this->accesosGrupo->eliminar($idDestino, $idGrupo);
    }

    public function listarAccesosSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Jefe, 'La sección no existe.');
        return $this->accesosSeccion->listarUsuariosPorSeccion($idSeccion);
    }

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

    public function quitarAccesoSeccion(int $idUsuario, int $idSeccion, int $idDestino): void
    {
        $seccion = $this->seccion($idSeccion);
        $this->exigirJefeDeGrupo($idUsuario, $seccion->idGrupo);
        if ($this->accesosSeccion->buscar($idDestino, $idSeccion) === null) {
            throw new NoEncontradoException('Ese usuario no está asignado a la sección.');
        }
        $this->accesosSeccion->eliminar($idDestino, $idSeccion);
    }

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

    private function exigirJefeDeGrupo(int $idUsuario, int $idGrupo): void
    {
        if ($this->grupos->buscarPorId($idGrupo) === null || !$this->permisos->tieneAccesoAlGrupo($idUsuario, $idGrupo)) {
            throw new NoEncontradoException('El grupo no existe.');
        }
        $this->permisos->exigirEnGrupo($idUsuario, $idGrupo, NivelRol::Jefe);
    }

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