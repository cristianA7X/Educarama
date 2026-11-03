<?php
// src/Service/PatrullaService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Patrulla;
use Educarama\Repository\PatrullaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\Vista\FichaProtagonista;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;

/**
 * Pequeños equipos de una sección (Seisena, Patrulla o Equipo según la rama;
 * el término sale de `nomenclatura`).
 *
 * Reglas:
 *  - Leer: Ayudante. Crear, renombrar y asignar integrantes: Responsable.
 *    Eliminar: Jefe (los integrantes quedan sin patrulla; lo hace la base).
 *  - El nombre no se repite dentro de la sección.
 *  - Un protagonista solo puede estar en una patrulla de su misma sección.
 *  - Una patrulla no se pasa a otra sección.
 */
final class PatrullaService extends ServicioBase
{
    private PermisoService $permisos;
    private PatrullaRepository $patrullas;
    private ProtagonistaRepository $protagonistas;

    public function __construct(
        ?PermisoService $permisos = null,
        ?PatrullaRepository $patrullas = null,
        ?ProtagonistaRepository $protagonistas = null,
    ) {
        parent::__construct();
        $this->permisos = $permisos ?? new PermisoService();
        $this->patrullas = $patrullas ?? new PatrullaRepository();
        $this->protagonistas = $protagonistas ?? new ProtagonistaRepository();
    }

    // -----------------------------------------------------------------
    // Lectura (Ayudante en adelante)
    // -----------------------------------------------------------------

    /** @return Patrulla[] Las de la sección, por nombre. */
    public function listarPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante);

        return $this->patrullas->listarPorSeccion($idSeccion);
    }

    public function buscar(int $idUsuario, int $idPatrulla): Patrulla
    {
        return $this->cargar($idUsuario, $idPatrulla, NivelRol::Ayudante);
    }

    /** @return FichaProtagonista[] Los integrantes, por apellido y nombre. */
    public function integrantes(int $idUsuario, int $idPatrulla): array
    {
        $this->cargar($idUsuario, $idPatrulla, NivelRol::Ayudante);

        return $this->protagonistas->listarFichasPorPatrulla($idPatrulla);
    }

    // -----------------------------------------------------------------
    // Escritura
    // -----------------------------------------------------------------

    /** Crea la patrulla en la sección que trae el objeto y devuelve su id. */
    public function crear(int $idUsuario, Patrulla $patrulla): int
    {
        $this->permisos->exigirEnSeccion($idUsuario, $patrulla->idSeccion, NivelRol::Responsable);
        $patrulla->nombre = $this->validarNombre($patrulla->idSeccion, $patrulla->nombre, null);

        return $this->patrullas->insertar($patrulla);
    }

    public function renombrar(int $idUsuario, int $idPatrulla, string $nombre): void
    {
        $patrulla = $this->cargar($idUsuario, $idPatrulla, NivelRol::Responsable);
        $patrulla->nombre = $this->validarNombre($patrulla->idSeccion, $nombre, $idPatrulla);

        $this->patrullas->actualizar($patrulla);
    }

    /** Borra la patrulla. Sus integrantes quedan sin patrulla (ON DELETE SET NULL). */
    public function eliminar(int $idUsuario, int $idPatrulla): void
    {
        $this->cargar($idUsuario, $idPatrulla, NivelRol::Jefe);

        $this->patrullas->eliminar($idPatrulla);
    }

    /**
     * Pone al protagonista en la patrulla, o lo saca si $idPatrulla es null.
     * La patrulla tiene que ser de la sección del protagonista.
     */
    public function asignarIntegrante(int $idUsuario, int $idProtagonista, ?int $idPatrulla): void
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');

        $this->permisos->exigirEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable);
        $this->exigirDeSeccion($idPatrulla, $protagonista->idSeccion);

        $protagonista->idPatrulla = $idPatrulla;
        $this->protagonistas->actualizar($protagonista);
    }

    // -----------------------------------------------------------------
    // Validaciones (también las usa ProtagonistaService)
    // -----------------------------------------------------------------

    /**
     * Corta si la patrulla no existe o no es de la sección. null (sin patrulla) siempre pasa.
     *
     * @throws ReglaNegocioException
     */
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

    // -----------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------

    /** Busca la patrulla y controla el permiso en su sección. */
    private function cargar(int $idUsuario, int $idPatrulla, NivelRol $minimo): Patrulla
    {
        $patrulla = $this->patrullas->buscarPorId($idPatrulla)
            ?? throw new NoEncontradoException('La patrulla no existe.');

        $this->permisos->exigirEnSeccion($idUsuario, $patrulla->idSeccion, $minimo);

        return $patrulla;
    }

    /**
     * Devuelve el nombre sin espacios de más. Corta si queda vacío o si ya hay
     * otra patrulla con ese nombre en la sección ($excepto = la que se renombra).
     */
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
