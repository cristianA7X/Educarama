<?php // src/Service/SeccionService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Grupo;
use Educarama\Model\Seccion;
use Educarama\Repository\AccesoGrupoRepository;
use Educarama\Repository\AccesoSeccionRepository;
use Educarama\Repository\GrupoRepository;
use Educarama\Repository\NomenclaturaRepository;
use Educarama\Repository\PatrullaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\RamaRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Vista\ConteoSeccion;
use Educarama\Service\Vista\GrupoEnPantalla;
use Educarama\Service\Vista\SeccionEnPantalla;
use PDO;

final class SeccionService extends ServicioBase
{
    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly SeccionRepository $secciones,
        private readonly GrupoRepository $grupos,
        private readonly RamaRepository $ramas,
        private readonly NomenclaturaRepository $nomenclaturas,
        private readonly AccesoGrupoRepository $accesosGrupo,
        private readonly AccesoSeccionRepository $accesosSeccion,
        private readonly ProtagonistaRepository $protagonistas,
        private readonly PatrullaRepository $patrullas
    ) {
        parent::__construct($conexion);
    }

    public function encabezado(int $idUsuario, int $idSeccion): SeccionEnPantalla
    {
        $nivel = $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Supervisor, 'La sección no existe.');
        $seccion = $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');
        $grupo = $this->grupos->buscarPorId($seccion->idGrupo)
            ?? throw new \LogicException("El grupo {$seccion->idGrupo} no existe.");
        return $this->armar($seccion, $grupo, $nivel);
    }

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

    public function grupo(int $idUsuario, int $idGrupo): GrupoEnPantalla
    {
        $secciones = $this->listarPorGrupo($idUsuario, $idGrupo);
        $grupo = $this->grupos->buscarPorId($idGrupo)
            ?? throw new NoEncontradoException('El grupo no existe.');
        return new GrupoEnPantalla($grupo, $this->permisos->nivelEnGrupo($idUsuario, $idGrupo), $secciones);
    }

    public function misGrupos(int $idUsuario): array
    {
        $idsGrupo = [];
        foreach ($this->accesosGrupo->listarPorUsuario($idUsuario) as $acceso) {
            $idsGrupo[$acceso->idGrupo] = true;
        }
        foreach ($this->accesosSeccion->listarPorUsuario($idUsuario) as $acceso) {
            $seccion = $this->secciones->buscarPorId($acceso->idSeccion);
            if ($seccion !== null) {
                $idsGrupo[$seccion->idGrupo] = true;
            }
        }
        $grupos = array_map(fn (int $id) => $this->grupo($idUsuario, $id), array_keys($idsGrupo));
        usort($grupos, fn (GrupoEnPantalla $a, GrupoEnPantalla $b) => strcmp($a->grupo->codigo, $b->grupo->codigo));
        return $grupos;
    }

    public function conteos(int $idUsuario, int $idSeccion): ConteoSeccion
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Supervisor, 'La sección no existe.');
        $porPatrulla = [];
        $sinPatrulla = 0;
        $protagonistas = $this->protagonistas->listarPorSeccion($idSeccion);
        foreach ($protagonistas as $protagonista) {
            if ($protagonista->idPatrulla === null) {
                $sinPatrulla++;
            } else {
                $porPatrulla[$protagonista->idPatrulla] = ($porPatrulla[$protagonista->idPatrulla] ?? 0) + 1;
            }
        }
        $lista = [];
        foreach ($this->patrullas->listarPorSeccion($idSeccion) as $patrulla) {
            $lista[] = ['patrulla' => $patrulla, 'cantidad' => $porPatrulla[$patrulla->id] ?? 0];
        }
        return new ConteoSeccion(count($protagonistas), $lista, $sinPatrulla);
    }

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