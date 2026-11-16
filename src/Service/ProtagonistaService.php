<?php
// src/Service/ProtagonistaService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Almacen\AlmacenArchivos;
use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Persona;
use Educarama\Model\Protagonista;
use Educarama\Repository\PersonaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\Vista\FichaProtagonista;
use Educarama\Repository\Vista\ProtagonistaEnNomina;
use Educarama\Service\Vista\SeccionEnPantalla;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use PDO;

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
 *  - Sin ningún acceso a la sección: 404 (no se confirma que el protagonista
 *    existe). Con acceso pero sin el rol: 403.
 */
final class ProtagonistaService extends ServicioBase
{
    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly PatrullaService $patrullaService,
        private readonly ProtagonistaRepository $protagonistas,
        private readonly PersonaRepository $personas,
        private readonly SeccionRepository $secciones,
        private readonly AlmacenArchivos $almacen,
        private readonly SeccionService $seccionService
    ) {
        parent::__construct($conexion);
    }

    // -----------------------------------------------------------------
    // Lectura (Ayudante en adelante)
    // -----------------------------------------------------------------

    /**
     * Nómina general: todos los protagonistas de las secciones a las que el
     * usuario tiene acceso (Ayudante en adelante), con progresión básica.
     * Para la tabla/nómina transversal entre secciones y grupos.
     *
     * @return ProtagonistaEnNomina[]
     */
    public function nomina(int $idUsuario): array
    {
        $idsSecciones = [];
        foreach ($this->seccionService->misGrupos($idUsuario) as $grupo) {
            foreach ($grupo->secciones as $seccion) {
                if ($seccion->puedeVerDetalle()) {
                    $idsSecciones[] = (int) $seccion->seccion->id;
                }
            }
        }

        return $this->protagonistas->listarNomina($idsSecciones);
    }

    /** Nómina de una sola sección (para la vista tabla en la pantalla de la sección). */
    public function nominaPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante, 'La sección no existe.');
        return $this->protagonistas->listarNomina([$idSeccion]);
    }

    /** @return FichaProtagonista[] Los de la sección, por apellido y nombre. */
    public function listarPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante, 'La sección no existe.');

        return $this->protagonistas->listarFichasPorSeccion($idSeccion);
    }

    /** La ficha de un protagonista, para su perfil o para precargar el formulario de edición. */
    public function buscarFicha(int $idUsuario, int $idProtagonista): FichaProtagonista
    {
        $ficha = $this->protagonistas->buscarFicha($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');

        $this->permisos->exigirVisibleEnSeccion($idUsuario, $ficha->protagonista->idSeccion, NivelRol::Ayudante, 'El protagonista no existe.');

        return $ficha;
    }

    /**
     * Las secciones a las que se puede pasar al protagonista: las otras de su
     * grupo donde el usuario es Responsable o más (cambiarSeccion() exige
     * Responsable en las dos). Vacía si no puede hacer ningún pase.
     *
     * @return SeccionEnPantalla[]
     */
    public function seccionesDestino(int $idUsuario, int $idProtagonista): array
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Ayudante, 'El protagonista no existe.');

        if (!$this->permisos->puedeEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable)) {
            return [];
        }

        $origen = $this->secciones->buscarPorId($protagonista->idSeccion)
            ?? throw new \LogicException("La sección {$protagonista->idSeccion} no existe.");

        return array_values(array_filter(
            $this->seccionService->listarPorGrupo($idUsuario, $origen->idGrupo),
            fn (SeccionEnPantalla $s) => $s->seccion->id !== $origen->id && $s->puedeEditar(),
        ));
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
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable, 'La sección no existe.');

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

        $this->permisos->exigirVisibleEnSeccion($idUsuario, $guardado->idSeccion, NivelRol::Responsable, 'El protagonista no existe.');

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

        // Primero el permiso en el origen: sin acceso, 404 antes de decir nada más.
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Responsable, 'El protagonista no existe.');

        if ($protagonista->idSeccion === $idSeccionDestino) {
            throw new ReglaNegocioException('El protagonista ya está en esa sección.');
        }

        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccionDestino, NivelRol::Responsable, 'La sección de destino no existe.');

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
     *
     * La base borra las filas de `documento`, pero no los archivos: después de
     * borrar en la base se borra su carpeta del almacén. Si eso falla, el
     * protagonista ya está borrado: se anota en el log del servidor y no se le
     * muestra un error al usuario (los archivos quedan sin fila, no a la vista).
     */
    public function eliminar(int $idUsuario, int $idProtagonista): void
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');

        $this->permisos->exigirVisibleEnSeccion($idUsuario, $protagonista->idSeccion, NivelRol::Jefe, 'El protagonista no existe.');

        $this->protagonistas->eliminar($idProtagonista);

        try {
            $this->almacen->borrarCarpeta(DocumentoService::carpetaDe($idProtagonista));
        } catch (\Throwable $e) {
            error_log("No se pudo borrar la carpeta de documentos del protagonista $idProtagonista: " . $e->getMessage());
        }
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
