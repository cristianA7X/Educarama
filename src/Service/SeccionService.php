<?php
// src/Service/SeccionService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Grupo;
use Educarama\Model\Seccion;
use Educarama\Repository\GrupoRepository;
use Educarama\Repository\NomenclaturaRepository;
use Educarama\Repository\RamaRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Vista\SeccionEnPantalla;

/**
 * Las secciones como tema propio: el encabezado de sus páginas y el listado
 * de las secciones de un grupo. Es la base del futuro perfil de sección.
 *
 * Las secciones no se crean ni se renombran acá: hoy solo nacen al aprobar una
 * solicitud de alta (SolicitudAltaService).
 *
 * Permisos (CLAUDE.md, "Reglas de negocio"):
 *  - Encabezado de una sección: cualquier acceso a ESA sección (el Jefe de
 *    grupo lo tiene por derivación). Sin acceso, 404.
 *  - Listado de un grupo: cualquier rol de grupo ve todas sus secciones (los
 *    roles de grupo valen para "el listado de secciones"); sin rol de grupo,
 *    solo las secciones a las que tiene acceso. Sin nada, 404.
 */
final class SeccionService extends ServicioBase
{
    private PermisoService $permisos;
    private SeccionRepository $secciones;
    private GrupoRepository $grupos;
    private RamaRepository $ramas;
    private NomenclaturaRepository $nomenclaturas;

    public function __construct(
        ?PermisoService $permisos = null,
        ?SeccionRepository $secciones = null,
        ?GrupoRepository $grupos = null,
        ?RamaRepository $ramas = null,
        ?NomenclaturaRepository $nomenclaturas = null,
    ) {
        parent::__construct();
        $this->permisos = $permisos ?? new PermisoService();
        $this->secciones = $secciones ?? new SeccionRepository();
        $this->grupos = $grupos ?? new GrupoRepository();
        $this->ramas = $ramas ?? new RamaRepository();
        $this->nomenclaturas = $nomenclaturas ?? new NomenclaturaRepository();
    }

    /**
     * Encabezado de las páginas de una sección (listado de protagonistas,
     * patrullas...). Basta cualquier acceso a la sección, incluso Supervisor:
     * qué más puede ver lo decide cada página con puedeVerDetalle(), puedeEditar()...
     *
     * @throws NoEncontradoException si no existe o el usuario no tiene acceso
     */
    public function encabezado(int $idUsuario, int $idSeccion): SeccionEnPantalla
    {
        $nivel = $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Supervisor, 'La sección no existe.');

        $seccion = $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');
        $grupo = $this->grupos->buscarPorId($seccion->idGrupo)
            ?? throw new \LogicException("El grupo {$seccion->idGrupo} no existe.");

        return $this->armar($seccion, $grupo, $nivel);
    }

    /**
     * Las secciones del grupo que el usuario puede ver, en el orden de la base.
     * Cada una trae el rol del usuario en ella (null si la ve solo por su rol de grupo).
     *
     * @return SeccionEnPantalla[]
     * @throws NoEncontradoException si el grupo no existe o el usuario no ve nada de él
     */
    public function listarPorGrupo(int $idUsuario, int $idGrupo): array
    {
        $grupo = $this->grupos->buscarPorId($idGrupo)
            ?? throw new NoEncontradoException('El grupo no existe.');
        $tieneRolDeGrupo = $this->permisos->nivelEnGrupo($idUsuario, $idGrupo) !== null;

        $lista = [];
        foreach ($this->secciones->listarPorGrupo($idGrupo) as $seccion) {
            $nivel = $this->permisos->nivelEnSeccion($idUsuario, (int) $seccion->id);
            if ($tieneRolDeGrupo || $nivel !== null) {
                $lista[] = $this->armar($seccion, $grupo, $nivel);
            }
        }

        if ($lista === [] && !$tieneRolDeGrupo) {
            throw new NoEncontradoException('El grupo no existe.');
        }

        return $lista;
    }

    // -----------------------------------------------------------------

    private function armar(Seccion $seccion, Grupo $grupo, ?NivelRol $nivel): SeccionEnPantalla
    {
        $rama = $this->ramas->buscarPorId($seccion->idRama)
            ?? throw new \LogicException("La rama {$seccion->idRama} no existe.");

        return new SeccionEnPantalla(
            seccion: $seccion,
            rama: $rama,
            grupo: $grupo,
            terminos: array_map(fn ($n) => $n->texto, $this->nomenclaturas->listarPorRama($rama->id)),
            nivel: $nivel,
        );
    }
}
