<?php // src/Service/PatrullaService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Patrulla;
use Educarama\Repository\NomenclaturaRepository;
use Educarama\Repository\PatrullaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use PDO;

final class PatrullaService extends ServicioBase
{
    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly PatrullaRepository $patrullas,
        private readonly ProtagonistaRepository $protagonistas,
        private readonly SeccionRepository $secciones,
        private readonly NomenclaturaRepository $nomenclaturas
    ) {
        parent::__construct($conexion);
    }

    public function listarPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante, 'La sección no existe.');
        return $this->patrullas->listarPorSeccion($idSeccion);
    }

    public function buscar(int $idUsuario, int $idPatrulla): Patrulla
    {
        return $this->cargar($idUsuario, $idPatrulla, NivelRol::Ayudante);
    }

    public function integrantes(int $idUsuario, int $idPatrulla): array
    {
        $this->cargar($idUsuario, $idPatrulla, NivelRol::Ayudante);
        return $this->protagonistas->listarFichasPorPatrulla($idPatrulla);
    }

    public function crear(int $idUsuario, Patrulla $patrulla): int
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $patrulla->idSeccion, NivelRol::Responsable, 'La sección no existe.');
        $this->exigirPequenioEquipo($patrulla->idSeccion);
        $patrulla->nombre = $this->validarNombre($patrulla->idSeccion, $patrulla->nombre, null);
        return $this->patrullas->insertar($patrulla);
    }

    public function renombrar(int $idUsuario, int $idPatrulla, string $nombre): void
    {
        $patrulla = $this->cargar($idUsuario, $idPatrulla, NivelRol::Responsable);
        $patrulla->nombre = $this->validarNombre($patrulla->idSeccion, $nombre, $idPatrulla);
        $this->patrullas->actualizar($patrulla);
    }

    public function eliminar(int $idUsuario, int $idPatrulla): void
    {
        $this->cargar($idUsuario, $idPatrulla, NivelRol::Jefe);
        $this->patrullas->eliminar($idPatrulla);
    }

    public function asignarIntegrante(int $idUsuario, int $idProtagonista, ?int $idPatrulla): void
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable, 'El protagonista no existe.');
        $this->exigirDeSeccion($idPatrulla, $protagonista->idSeccion);
        $protagonista->idPatrulla = $idPatrulla;
        $this->protagonistas->actualizar($protagonista);
    }

    public function quitarIntegrante(int $idUsuario, int $idPatrulla, int $idProtagonista): void
    {
        $this->cargar($idUsuario, $idPatrulla, NivelRol::Responsable);
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista);
        if ($protagonista === null || $protagonista->idPatrulla !== $idPatrulla) {
            throw new ReglaNegocioException('El protagonista no es integrante de esta patrulla.');
        }
        $protagonista->idPatrulla = null;
        $this->protagonistas->actualizar($protagonista);
    }

    public function exigirDeSeccion(?int $idPatrulla, int $idSeccion): void
    {
        if ($idPatrulla === null) {
            return;
        }
        $patrulla = $this->patrullas->buscarPorId($idPatrulla);
        if ($patrulla === null || $patrulla->idSeccion !== $idSeccion) {
            throw new ReglaNegocioException('La patrulla elegida no es de la sección del protagonista.');
        }
    }

    private function exigirPequenioEquipo(int $idSeccion): void
    {
        $seccion = $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');
        if ($this->nomenclaturas->buscar('pequenio_equipo', $seccion->idRama) === null) {
            throw new ReglaNegocioException('Esta rama no tiene pequeños equipos.');
        }
    }

    private function cargar(int $idUsuario, int $idPatrulla, NivelRol $minimo): Patrulla
    {
        $patrulla = $this->patrullas->buscarPorId($idPatrulla)
            ?? throw new NoEncontradoException('La patrulla no existe.');
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $patrulla->idSeccion, $minimo, 'La patrulla no existe.');
        return $patrulla;
    }

    private function validarNombre(int $idSeccion, string $nombre, ?int $excepto): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new ReglaNegocioException('El nombre de la patrulla es obligatorio.');
        }
        $existente = $this->patrullas->buscarPorNombre($idSeccion, $nombre);
        if ($existente !== null && $existente->id !== $excepto) {
            throw new ReglaNegocioException("Ya hay una patrulla llamada {$existente->nombre} en esta sección.");
        }
        return $nombre;
    }
}