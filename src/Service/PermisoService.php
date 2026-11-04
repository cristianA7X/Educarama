<?php
// src/Service/PermisoService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Repository\AccesoGrupoRepository;
use Educarama\Repository\AccesoSeccionRepository;
use Educarama\Repository\RolRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\UsuarioRepository;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;

/**
 * Calcula qué rol tiene un usuario sobre un grupo o una sección, y corta la
 * operación si no le alcanza.
 *
 * Reglas (ver CLAUDE.md, "Reglas de negocio"):
 *  - En una sección:
 *      1. El Jefe de grupo es Jefe de todas las secciones de su grupo (por
 *         derivación: no tiene filas en acceso_seccion).
 *      2. Si no, vale su fila de acceso_seccion.
 *      3. Si no tiene fila, no tiene acceso. Los otros roles de grupo
 *         (Supervisor, Ayudante, Responsable) NO se heredan a las secciones.
 *  - En un grupo: vale solo su fila de acceso_grupo. Sirve para las pantallas
 *    del grupo (estadísticas, listado de secciones, eventos grupales).
 *  - El admin (usuario.es_admin) no tiene acceso a nada por ser admin: solo
 *    aprueba o rechaza solicitudes de alta (exigirAdmin).
 *
 * Uso típico dentro de otro Service, antes de escribir:
 *
 *   $this->permisos->exigirEnSeccion($idUsuario, $idSeccion, NivelRol::Responsable);
 */
final class PermisoService extends ServicioBase
{
    private AccesoGrupoRepository $accesosGrupo;
    private AccesoSeccionRepository $accesosSeccion;
    private RolRepository $roles;
    private SeccionRepository $secciones;
    private UsuarioRepository $usuarios;

    public function __construct(
        ?AccesoGrupoRepository $accesosGrupo = null,
        ?AccesoSeccionRepository $accesosSeccion = null,
        ?RolRepository $roles = null,
        ?SeccionRepository $secciones = null,
        ?UsuarioRepository $usuarios = null,
    ) {
        parent::__construct();
        $this->accesosGrupo = $accesosGrupo ?? new AccesoGrupoRepository();
        $this->accesosSeccion = $accesosSeccion ?? new AccesoSeccionRepository();
        $this->roles = $roles ?? new RolRepository();
        $this->secciones = $secciones ?? new SeccionRepository();
        $this->usuarios = $usuarios ?? new UsuarioRepository();
    }

    // -----------------------------------------------------------------
    // Grupo
    // -----------------------------------------------------------------

    /** El rol del usuario en el grupo, o null si no tiene acceso. */
    public function nivelEnGrupo(int $idUsuario, int $idGrupo): ?NivelRol
    {
        $acceso = $this->accesosGrupo->buscar($idUsuario, $idGrupo);

        return $acceso === null ? null : $this->nivelDeRol($acceso->idRol);
    }

    public function esJefeDeGrupo(int $idUsuario, int $idGrupo): bool
    {
        return $this->nivelEnGrupo($idUsuario, $idGrupo) === NivelRol::Jefe;
    }

    /** Para mostrar u ocultar botones en las pantallas del grupo. */
    public function puedeEnGrupo(int $idUsuario, int $idGrupo, NivelRol $minimo): bool
    {
        return $this->alcanza($this->nivelEnGrupo($idUsuario, $idGrupo), $minimo);
    }

    /**
     * Corta la operación si el usuario no tiene en el grupo al menos el rol $minimo.
     * Devuelve el rol que tiene, por si el Service lo necesita.
     *
     * @throws AccesoDenegadoException
     */
    public function exigirEnGrupo(int $idUsuario, int $idGrupo, NivelRol $minimo): NivelRol
    {
        $nivel = $this->nivelEnGrupo($idUsuario, $idGrupo);

        if (!$this->alcanza($nivel, $minimo)) {
            throw new AccesoDenegadoException(
                'Necesitás el rol ' . $minimo->etiqueta() . ' en este grupo para hacer esto.'
            );
        }

        return $nivel;
    }

    // -----------------------------------------------------------------
    // Sección
    // -----------------------------------------------------------------

    /**
     * El rol del usuario en la sección, o null si no tiene acceso.
     *
     * @throws NoEncontradoException si la sección no existe
     */
    public function nivelEnSeccion(int $idUsuario, int $idSeccion): ?NivelRol
    {
        $seccion = $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');

        // 1. El Jefe de grupo es Jefe de todas sus secciones, tenga o no fila en acceso_seccion.
        if ($this->esJefeDeGrupo($idUsuario, $seccion->idGrupo)) {
            return NivelRol::Jefe;
        }

        // 2 y 3. Si no, solo vale la fila de la sección. Otros roles de grupo no se heredan.
        $acceso = $this->accesosSeccion->buscar($idUsuario, $idSeccion);

        return $acceso === null ? null : $this->nivelDeRol($acceso->idRol);
    }

    /** Para mostrar u ocultar botones en las pantallas de la sección. */
    public function puedeEnSeccion(int $idUsuario, int $idSeccion, NivelRol $minimo): bool
    {
        return $this->alcanza($this->nivelEnSeccion($idUsuario, $idSeccion), $minimo);
    }

    /**
     * Corta la operación si el usuario no tiene en la sección al menos el rol $minimo.
     * Devuelve el rol que tiene, por si el Service lo necesita.
     *
     * @throws AccesoDenegadoException
     * @throws NoEncontradoException si la sección no existe
     */
    public function exigirEnSeccion(int $idUsuario, int $idSeccion, NivelRol $minimo): NivelRol
    {
        $nivel = $this->nivelEnSeccion($idUsuario, $idSeccion);

        if (!$this->alcanza($nivel, $minimo)) {
            throw new AccesoDenegadoException(
                'Necesitás el rol ' . $minimo->etiqueta() . ' en esta sección para hacer esto.'
            );
        }

        return $nivel;
    }

    /**
     * Como exigirEnSeccion(), pero si el usuario no tiene NINGÚN acceso a la
     * sección lanza NoEncontradoException (404) con $mensaje404, igual que si
     * lo pedido no existiera: así un usuario de otro grupo no puede averiguar
     * qué hay. Con acceso pero sin el rol $minimo, AccesoDenegadoException (403):
     * lo pedido ya lo puede ver.
     *
     * $mensaje404 es el de "no existe" de lo que se buscaba ("El protagonista no existe.").
     *
     * @throws NoEncontradoException
     * @throws AccesoDenegadoException
     */
    public function exigirVisibleEnSeccion(int $idUsuario, int $idSeccion, NivelRol $minimo, string $mensaje404): NivelRol
    {
        $nivel = $this->nivelEnSeccion($idUsuario, $idSeccion)
            ?? throw new NoEncontradoException($mensaje404);

        if (!$this->alcanza($nivel, $minimo)) {
            throw new AccesoDenegadoException(
                'Necesitás el rol ' . $minimo->etiqueta() . ' en esta sección para hacer esto.'
            );
        }

        return $nivel;
    }

    // -----------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------

    /**
     * Corta la operación si el usuario no es admin. Solo lo usan las
     * pantallas de aprobación de solicitudes de alta.
     *
     * @throws AccesoDenegadoException
     */
    public function exigirAdmin(int $idUsuario): void
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);

        if ($usuario === null || !$usuario->esAdmin) {
            throw new AccesoDenegadoException('Solo un administrador puede hacer esto.');
        }
    }

    // -----------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------

    /** Traduce un id de `rol` a su NivelRol (la tabla rol está en caché: no repite la consulta). */
    private function nivelDeRol(int $idRol): NivelRol
    {
        $rol = $this->roles->buscarPorId($idRol)
            ?? throw new \LogicException("El rol $idRol no existe en la tabla rol.");

        return NivelRol::from($rol->nivel);
    }

    /** Sin acceso (null) nunca alcanza; si no, compara los niveles 1 a 4. */
    private function alcanza(?NivelRol $nivel, NivelRol $minimo): bool
    {
        return $nivel !== null && $nivel->value >= $minimo->value;
    }
}
