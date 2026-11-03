<?php
// src/Service/ProtagonistaService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Persona;
use Educarama\Model\Protagonista;
use Educarama\Repository\PersonaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\Vista\FichaProtagonista;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;

/**
 * Alta, edición, pase de sección y baja de protagonistas, con sus datos
 * personales (`persona`).
 *
 * Reglas:
 *  - Leer: Ayudante (el Supervisor no ve datos personales). Alta, edición y
 *    pase de sección: Responsable. Eliminar: Jefe.
 *  - Recibe Models ya armados por el Controller (Persona + Protagonista); este
 *    Service los valida y los guarda. Los textos se guardan sin espacios de más.
 *  - Obligatorios: DNI (entero positivo), nombre y apellido (Validacion::persona).
 *    La fecha de nacimiento es opcional; el año de ingreso, si se carga, no puede ser futuro.
 *  - El DNI es único: si ya existe en `persona`, se rechaza (aunque sea un familiar).
 *  - La patrulla tiene que ser de la misma sección.
 *  - La sección se cambia solo con cambiarSeccion(), dentro del mismo grupo.
 */
final class ProtagonistaService extends ServicioBase
{
    private PermisoService $permisos;
    private PatrullaService $patrullaService;
    private ProtagonistaRepository $protagonistas;
    private PersonaRepository $personas;
    private SeccionRepository $secciones;

    public function __construct(
        ?PermisoService $permisos = null,
        ?PatrullaService $patrullaService = null,
        ?ProtagonistaRepository $protagonistas = null,
        ?PersonaRepository $personas = null,
        ?SeccionRepository $secciones = null,
    ) {
        parent::__construct();
        $this->permisos = $permisos ?? new PermisoService();
        $this->patrullaService = $patrullaService ?? new PatrullaService($this->permisos);
        $this->protagonistas = $protagonistas ?? new ProtagonistaRepository();
        $this->personas = $personas ?? new PersonaRepository();
        $this->secciones = $secciones ?? new SeccionRepository();
    }

    // -----------------------------------------------------------------
    // Lectura (Ayudante en adelante)
    // -----------------------------------------------------------------

    /** @return FichaProtagonista[] Los de la sección, por apellido y nombre. */
    public function listarPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante);

        return $this->protagonistas->listarFichasPorSeccion($idSeccion);
    }

    /** La ficha de un protagonista, para su perfil o para precargar el formulario de edición. */
    public function buscarFicha(int $idUsuario, int $idProtagonista): FichaProtagonista
    {
        $ficha = $this->protagonistas->buscarFicha($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');

        $this->permisos->exigirEnSeccion($idUsuario, $ficha->protagonista->idSeccion, NivelRol::Ayudante);

        return $ficha;
    }

    // -----------------------------------------------------------------
    // Escritura
    // -----------------------------------------------------------------

    /**
     * Da de alta un protagonista nuevo con su persona, en una transacción.
     * Devuelve el id del protagonista.
     *
     * La sección sale de $protagonista->idSeccion. $protagonista->idPersona se
     * ignora (el Controller puede pasar 0): se completa con la persona recién creada.
     *
     * @throws ReglaNegocioException si falta un dato, el DNI ya existe o la patrulla es de otra sección
     */
    public function alta(int $idUsuario, Persona $persona, Protagonista $protagonista): int
    {
        $this->permisos->exigirEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable);

        $this->validarPersona($persona, null);
        $this->validarProtagonista($protagonista);

        return $this->transaccion(function () use ($persona, $protagonista): int {
            $protagonista->idPersona = $this->personas->insertar($persona);

            return $this->protagonistas->insertar($protagonista);
        });
    }

    /**
     * Guarda los cambios de datos personales, patrulla, promesa y año de ingreso.
     *
     * Los ids salen de la base, no de los objetos recibidos: $protagonista->id
     * indica a quién se edita, pero su persona y su sección son las guardadas.
     * Si la sección del objeto es otra, se rechaza: para eso está cambiarSeccion().
     */
    public function actualizar(int $idUsuario, Persona $persona, Protagonista $protagonista): void
    {
        $guardado = $this->protagonistas->buscarPorId($this->idDe($protagonista))
            ?? throw new NoEncontradoException('El protagonista no existe.');

        $this->permisos->exigirEnSeccion($idUsuario, $guardado->idSeccion, NivelRol::Responsable);

        if ($protagonista->idSeccion !== $guardado->idSeccion) {
            throw new ReglaNegocioException('Para pasar al protagonista a otra sección usá "Cambiar de sección".');
        }

        // Se fuerzan los ids guardados, así un formulario adulterado no edita a otra persona.
        $protagonista->idPersona = $guardado->idPersona;
        $persona->id = $guardado->idPersona;

        $this->validarPersona($persona, $guardado->idPersona);
        $this->validarProtagonista($protagonista);

        $this->transaccion(function () use ($persona, $protagonista): void {
            $this->personas->actualizar($persona);
            $this->protagonistas->actualizar($protagonista);
        });
    }

    /**
     * Pasa al protagonista a otra sección del mismo grupo (ej. de la Manada a
     * la Unidad). Exige Responsable en las dos secciones; el Jefe de grupo las
     * tiene a todas. Queda sin patrulla. Su progresión de la rama anterior
     * queda guardada como historial: ProgresionService cuenta solo la de la rama nueva.
     */
    public function cambiarSeccion(int $idUsuario, int $idProtagonista, int $idSeccionDestino): void
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');

        if ($protagonista->idSeccion === $idSeccionDestino) {
            throw new ReglaNegocioException('El protagonista ya está en esa sección.');
        }

        $this->permisos->exigirEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable);
        $this->permisos->exigirEnSeccion($idUsuario, $idSeccionDestino, NivelRol::Responsable);

        $origen = $this->secciones->buscarPorId($protagonista->idSeccion)
            ?? throw new \LogicException("La sección {$protagonista->idSeccion} no existe.");
        $destino = $this->secciones->buscarPorId($idSeccionDestino)
            ?? throw new NoEncontradoException('La sección de destino no existe.');

        if ($origen->idGrupo !== $destino->idGrupo) {
            throw new ReglaNegocioException('Solo se puede pasar a una sección del mismo grupo.');
        }

        $protagonista->idSeccion = $idSeccionDestino;
        $protagonista->idPatrulla = null;  // Las patrullas son de cada sección
        $this->protagonistas->actualizar($protagonista);
    }

    /**
     * Borra al protagonista y, en cascada, su progresión, documentos y vínculos
     * con familiares. Su persona queda: si vuelve a entrar, el DNI ya está cargado.
     */
    public function eliminar(int $idUsuario, int $idProtagonista): void
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');

        $this->permisos->exigirEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Jefe);

        $this->protagonistas->eliminar($idProtagonista);
    }

    // -----------------------------------------------------------------
    // Validaciones
    // -----------------------------------------------------------------

    /**
     * Datos obligatorios y DNI único. Limpia los espacios de nombre y apellido.
     *
     * @param ?int $idPersonaPropia La persona que se edita (su propio DNI no cuenta como repetido)
     */
    private function validarPersona(Persona $persona, ?int $idPersonaPropia): void
    {
        Validacion::persona($persona);

        $conEseDni = $this->personas->buscarPorDni($persona->dni);
        if ($conEseDni !== null && $conEseDni->id !== $idPersonaPropia) {
            throw new ReglaNegocioException('Ya existe una persona con ese DNI.');
        }
    }

    /** Año de ingreso y patrulla de la misma sección. */
    private function validarProtagonista(Protagonista $protagonista): void
    {
        if ($protagonista->anioIngreso !== null) {
            if ($protagonista->anioIngreso <= 0) {
                throw new ReglaNegocioException('El año de ingreso no es válido.');
            }
            if ($protagonista->anioIngreso > (int) date('Y')) {
                throw new ReglaNegocioException('El año de ingreso no puede ser futuro.');
            }
        }

        $this->patrullaService->exigirDeSeccion($protagonista->idPatrulla, $protagonista->idSeccion);
    }

    /** El id del protagonista que se quiere editar. */
    private function idDe(Protagonista $protagonista): int
    {
        return $protagonista->id
            ?? throw new \InvalidArgumentException('Para actualizar hace falta el id del protagonista.');
    }
}
