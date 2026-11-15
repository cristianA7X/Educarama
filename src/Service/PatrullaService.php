<?php
// src/Service/PatrullaService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Patrulla;
use Educarama\Repository\NomenclaturaRepository;
use Educarama\Repository\PatrullaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\SeccionRepository;
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
 *  - Solo en ramas con pequeño equipo: las que tienen el término
 *    'pequenio_equipo' en nomenclatura (hoy todas menos Rovers).
 *  - Sin ningún acceso a la sección: 404 (no se confirma que existe). Con
 *    acceso pero sin el rol: 403.
 */
final class PatrullaService extends ServicioBase
{
    private PermisoService $permisos;
    private PatrullaRepository $patrullas;
    private ProtagonistaRepository $protagonistas;
    private SeccionRepository $secciones;
    private NomenclaturaRepository $nomenclaturas;

    public function __construct(
        ?PermisoService $permisos = null,
        ?PatrullaRepository $patrullas = null,
        ?ProtagonistaRepository $protagonistas = null,
        ?SeccionRepository $secciones = null,
        ?NomenclaturaRepository $nomenclaturas = null,
    ) {
        parent::__construct();
        $this->permisos = $permisos ?? new PermisoService();
        $this->patrullas = $patrullas ?? new PatrullaRepository();
        $this->protagonistas = $protagonistas ?? new ProtagonistaRepository();
        $this->secciones = $secciones ?? new SeccionRepository();
        $this->nomenclaturas = $nomenclaturas ?? new NomenclaturaRepository();
    }

    // -----------------------------------------------------------------
    // Lectura (Ayudante en adelante)
    // -----------------------------------------------------------------

    /** @return Patrulla[] Las de la sección, por nombre. */
    public function listarPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante, 'La sección no existe.');

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

        $this->permisos->exigirVisibleEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable, 'El protagonista no existe.');
        $this->exigirDeSeccion($idPatrulla, $protagonista->idSeccion);

        $protagonista->idPatrulla = $idPatrulla;
        $this->protagonistas->actualizar($protagonista);
    }

    /**
     * Saca al protagonista de ESTA patrulla (desde el perfil de la patrulla).
     * Si no es integrante, se rechaza: así un pedido viejo o adulterado no lo
     * saca de otra patrulla a la que se pasó mientras tanto.
     */
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

    /**
     * Corta si la rama de la sección no tiene pequeño equipo (Rovers). Se
     * decide por nomenclatura y no por el id de la rama: es el mismo dato que
     * usa la vista para mostrar u ocultar "Seisena / Patrulla / Equipo".
     */
    private function exigirPequenioEquipo(int $idSeccion): void
    {
        $seccion = $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');

        if ($this->nomenclaturas->buscar('pequenio_equipo', $seccion->idRama) === null) {
            throw new ReglaNegocioException('Esta rama no tiene pequeños equipos.');
        }
    }

    /** Busca la patrulla y controla el permiso en su sección. */
    private function cargar(int $idUsuario, int $idPatrulla, NivelRol $minimo): Patrulla
    {
        $patrulla = $this->patrullas->buscarPorId($idPatrulla)
            ?? throw new NoEncontradoException('La patrulla no existe.');

        $this->permisos->exigirVisibleEnSeccion($idUsuario, $patrulla->idSeccion, $minimo, 'La patrulla no existe.');

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
