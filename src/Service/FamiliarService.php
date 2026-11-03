<?php
// src/Service/FamiliarService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\EsFamilia;
use Educarama\Model\Familiar;
use Educarama\Model\Persona;
use Educarama\Model\Protagonista;
use Educarama\Repository\EsFamiliaRepository;
use Educarama\Repository\FamiliarRepository;
use Educarama\Repository\PersonaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\Vista\FichaFamiliar;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Excepcion\RequisitosPendientesException;
use Educarama\Service\Vista\Aviso;
use Educarama\Service\Vista\TipoAviso;

/**
 * Familiares de los protagonistas: sus datos (`persona` + `familiar`) y el
 * vínculo con cada protagonista (`es_familia`).
 *
 * Todo se hace "desde un protagonista": el permiso se controla en la sección
 * de ese protagonista, y el familiar tiene que estar vinculado a él. Un mismo
 * familiar (ej. la madre de dos hermanos) es una sola fila, vinculada a varios.
 *
 * Reglas:
 *  - Leer: Ayudante. Alta, edición y desvincular: Responsable.
 *  - Obligatorios: DNI, nombre y apellido. Teléfono y email son opcionales; el
 *    email, si viene, tiene que ser válido. Contactos de emergencia: libres.
 *  - Alta con un DNI que ya existe: avisa con el nombre (sin decir a quién más
 *    está vinculado) y, si se confirma, solo crea el vínculo. No pisa los datos guardados.
 *  - Desvincular: si el familiar queda sin protagonistas, avisa con su nombre
 *    y, si se confirma, borra su ficha de familiar. Su persona queda.
 */
final class FamiliarService extends ServicioBase
{
    private PermisoService $permisos;
    private ProtagonistaRepository $protagonistas;
    private PersonaRepository $personas;
    private FamiliarRepository $familiares;
    private EsFamiliaRepository $vinculos;

    public function __construct(
        ?PermisoService $permisos = null,
        ?ProtagonistaRepository $protagonistas = null,
        ?PersonaRepository $personas = null,
        ?FamiliarRepository $familiares = null,
        ?EsFamiliaRepository $vinculos = null,
    ) {
        parent::__construct();
        $this->permisos = $permisos ?? new PermisoService();
        $this->protagonistas = $protagonistas ?? new ProtagonistaRepository();
        $this->personas = $personas ?? new PersonaRepository();
        $this->familiares = $familiares ?? new FamiliarRepository();
        $this->vinculos = $vinculos ?? new EsFamiliaRepository();
    }

    // -----------------------------------------------------------------
    // Lectura (Ayudante en adelante)
    // -----------------------------------------------------------------

    /** @return FichaFamiliar[] Primero los contactos de emergencia. */
    public function listarPorProtagonista(int $idUsuario, int $idProtagonista): array
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Ayudante);

        return $this->familiares->listarFichasPorProtagonista($idProtagonista);
    }

    /** Un familiar de ese protagonista, para precargar el formulario de edición. */
    public function buscarFicha(int $idUsuario, int $idProtagonista, int $idFamiliar): FichaFamiliar
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Ayudante);

        return $this->familiares->buscarFicha($idProtagonista, $idFamiliar)
            ?? throw new NoEncontradoException('El familiar no está vinculado a este protagonista.');
    }

    // -----------------------------------------------------------------
    // Escritura (Responsable)
    // -----------------------------------------------------------------

    /**
     * Agrega un familiar al protagonista y devuelve el id del familiar.
     *
     * El Controller arma los tres Models con lo del formulario. Los ids que
     * traigan se ignoran: se completan acá. De $vinculo se usan solo el
     * vínculo (ej. 'Madre') y si es contacto de emergencia.
     *
     * Si el DNI ya está cargado:
     *  - sin $confirmarIgual: RequisitosPendientesException con "Ana Pérez ya está cargada...";
     *  - con $confirmarIgual: se vincula esa persona, sin pisar sus datos guardados
     *    (si todavía no era familiar, se crea su ficha de familiar con el contacto del formulario).
     *
     * @throws RequisitosPendientesException
     * @throws ReglaNegocioException
     */
    public function alta(
        int $idUsuario,
        int $idProtagonista,
        Persona $persona,
        Familiar $familiar,
        EsFamilia $vinculo,
        bool $confirmarIgual = false,
    ): int {
        $protagonista = $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);

        Validacion::persona($persona);
        $this->validarContacto($familiar);
        $this->validarVinculo($vinculo);

        $existente = $this->personas->buscarPorDni($persona->dni);

        // --- DNI nuevo: se crean persona, familiar y vínculo ---
        if ($existente === null) {
            return $this->transaccion(function () use ($persona, $familiar, $vinculo, $idProtagonista): int {
                $familiar->idPersona = $this->personas->insertar($persona);
                $idFamiliar = $this->familiares->insertar($familiar);
                $this->vincular($idProtagonista, $idFamiliar, $vinculo);

                return $idFamiliar;
            });
        }

        // --- DNI ya cargado ---
        if ($existente->id === $protagonista->idPersona) {
            throw new ReglaNegocioException('Ese DNI es el del propio protagonista.');
        }

        $familiarExistente = $this->familiares->buscarPorPersona((int) $existente->id);
        if ($familiarExistente !== null && $this->vinculos->buscar($idProtagonista, (int) $familiarExistente->id) !== null) {
            throw new ReglaNegocioException("{$existente->nombre} {$existente->apellido} ya es familiar de este protagonista.");
        }

        if (!$confirmarIgual) {
            // Solo el nombre: no se dice a qué otros protagonistas está vinculada (pueden ser de otro grupo).
            throw new RequisitosPendientesException([new Aviso(
                TipoAviso::PersonaExistente,
                "{$existente->nombre} {$existente->apellido} ya está cargada con ese DNI. ¿Vincularla a este protagonista? Se van a conservar sus datos guardados.",
            )]);
        }

        return $this->transaccion(function () use ($existente, $familiarExistente, $familiar, $vinculo, $idProtagonista): int {
            $idFamiliar = $familiarExistente?->id;

            if ($idFamiliar === null) {
                // La persona existía pero no era familiar (ej. un hermano que es Rover).
                $familiar->idPersona = (int) $existente->id;
                $idFamiliar = $this->familiares->insertar($familiar);
            }

            $this->vincular($idProtagonista, $idFamiliar, $vinculo);

            return $idFamiliar;
        });
    }

    /**
     * Guarda los cambios de un familiar vinculado al protagonista: datos
     * personales, contacto y el vínculo con ESE protagonista. Los datos
     * personales y de contacto los ven todos sus protagonistas (es la misma persona).
     *
     * Los ids salen de la base: $familiar->id dice a quién se edita, pero su
     * persona es la guardada. Así un formulario adulterado no edita a otra persona.
     */
    public function actualizar(
        int $idUsuario,
        int $idProtagonista,
        Persona $persona,
        Familiar $familiar,
        EsFamilia $vinculo,
    ): void {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);

        $idFamiliar = $familiar->id
            ?? throw new \InvalidArgumentException('Para actualizar hace falta el id del familiar.');
        $guardada = $this->familiares->buscarFicha($idProtagonista, $idFamiliar)
            ?? throw new NoEncontradoException('El familiar no está vinculado a este protagonista.');

        // Ids forzados desde la base.
        $familiar->idPersona = $guardada->familiar->idPersona;
        $persona->id = $guardada->familiar->idPersona;
        $vinculo->idProtagonista = $idProtagonista;
        $vinculo->idFamiliar = $idFamiliar;

        Validacion::persona($persona);
        $this->validarContacto($familiar);
        $this->validarVinculo($vinculo);

        $conEseDni = $this->personas->buscarPorDni($persona->dni);
        if ($conEseDni !== null && $conEseDni->id !== $persona->id) {
            throw new ReglaNegocioException('Ya existe otra persona con ese DNI.');
        }

        $this->transaccion(function () use ($persona, $familiar, $vinculo): void {
            $this->personas->actualizar($persona);
            $this->familiares->actualizar($familiar);
            $this->vinculos->actualizar($vinculo);
        });
    }

    /**
     * Quita el vínculo entre el familiar y el protagonista.
     *
     * Si era su único protagonista, el familiar quedaría suelto: sin
     * $confirmarIgual avisa con su nombre; con $confirmarIgual borra su ficha
     * de familiar (la base borra el vínculo en cascada). Su persona queda.
     *
     * @throws RequisitosPendientesException
     */
    public function desvincular(int $idUsuario, int $idProtagonista, int $idFamiliar, bool $confirmarIgual = false): void
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);

        $ficha = $this->familiares->buscarFicha($idProtagonista, $idFamiliar)
            ?? throw new NoEncontradoException('El familiar no está vinculado a este protagonista.');

        $quedariaSuelto = count($this->vinculos->listarPorFamiliar($idFamiliar)) === 1;

        if (!$quedariaSuelto) {
            $this->vinculos->eliminar($idProtagonista, $idFamiliar);
            return;
        }

        if (!$confirmarIgual) {
            throw new RequisitosPendientesException([new Aviso(
                TipoAviso::FamiliarHuerfano,
                "{$ficha->persona->nombre} {$ficha->persona->apellido} no queda vinculada a ningún protagonista: se va a borrar su ficha de familiar.",
            )]);
        }

        $this->familiares->eliminar($idFamiliar);
    }

    // -----------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------

    /** Busca el protagonista y controla el permiso en su sección. */
    private function cargarProtagonista(int $idUsuario, int $idProtagonista, NivelRol $minimo): Protagonista
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');

        $this->permisos->exigirEnSeccion($idUsuario, $protagonista->idSeccion, $minimo);

        return $protagonista;
    }

    private function vincular(int $idProtagonista, int $idFamiliar, EsFamilia $vinculo): void
    {
        $vinculo->idProtagonista = $idProtagonista;
        $vinculo->idFamiliar = $idFamiliar;
        $this->vinculos->insertar($vinculo);
    }

    /** Teléfono (texto libre) y email opcionales. Limpia los espacios (modifica el objeto). */
    private function validarContacto(Familiar $familiar): void
    {
        $familiar->telefonoContacto = Validacion::textoOpcional($familiar->telefonoContacto, 30, 'El teléfono');
        $familiar->emailContacto = Validacion::emailOpcional($familiar->emailContacto);
    }

    private function validarVinculo(EsFamilia $vinculo): void
    {
        $vinculo->vinculo = Validacion::textoOpcional($vinculo->vinculo, 50, 'El vínculo');
    }
}
