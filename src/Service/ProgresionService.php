<?php // src/Service/ProgresionService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Accion;
use Educarama\Model\AccionCumplida;
use Educarama\Model\Competencia;
use Educarama\Model\CompetenciaProtagonista;
use Educarama\Model\Enum\Elemento;
use Educarama\Model\Enum\EstadoProgresion;
use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Especialidad;
use Educarama\Model\EspecialidadProtagonista;
use Educarama\Model\Etapa;
use Educarama\Model\EtapaLograda;
use Educarama\Model\InsigniaOtorgada;
use Educarama\Model\Rama;
use Educarama\Model\TareaEtapaCumplida;
use Educarama\Repository\AccionCumplidaRepository;
use Educarama\Repository\AccionRepository;
use Educarama\Repository\AreaRamaRepository;
use Educarama\Repository\AreaRepository;
use Educarama\Repository\CompetenciaProtagonistaRepository;
use Educarama\Repository\CompetenciaRepository;
use Educarama\Repository\EspecialidadProtagonistaRepository;
use Educarama\Repository\EspecialidadRepository;
use Educarama\Repository\EtapaLogradaRepository;
use Educarama\Repository\EtapaRepository;
use Educarama\Repository\InsigniaOtorgadaRepository;
use Educarama\Repository\InsigniaRepository;
use Educarama\Repository\NomenclaturaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\RamaRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\TareaEtapaCumplidaRepository;
use Educarama\Repository\TareaEtapaRepository;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Excepcion\RequisitosPendientesException;
use Educarama\Service\Vista\AccionEnPantalla;
use Educarama\Service\Vista\AreaEnPantalla;
use Educarama\Service\Vista\Aviso;
use Educarama\Service\Vista\CompetenciaEnPantalla;
use Educarama\Service\Vista\EspecialidadEnPantalla;
use Educarama\Service\Vista\EtapaEnPantalla;
use Educarama\Service\Vista\InsigniaEnPantalla;
use Educarama\Service\Vista\ContadorArea;
use Educarama\Service\Vista\PantallaCompetencias;
use Educarama\Service\Vista\PantallaEtapas;
use Educarama\Service\Vista\ResultadoProgresion;
use Educarama\Service\Vista\ResumenProgresion;
use Educarama\Service\Vista\TareaEnPantalla;
use Educarama\Service\Vista\TipoAviso;
use PDO;

final class ProgresionService extends ServicioBase
{
    private const ID_RAMA_CAMINANTES = 3;

    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly ProtagonistaRepository $protagonistas,
        private readonly SeccionRepository $secciones,
        private readonly RamaRepository $ramas,
        private readonly EtapaRepository $etapas,
        private readonly CompetenciaRepository $competencias,
        private readonly AccionRepository $acciones,
        private readonly TareaEtapaRepository $tareas,
        private readonly InsigniaRepository $insignias,
        private readonly EspecialidadRepository $especialidades,
        private readonly AreaRamaRepository $areasRama,
        private readonly CompetenciaProtagonistaRepository $competenciasProtagonista,
        private readonly AccionCumplidaRepository $accionesCumplidas,
        private readonly TareaEtapaCumplidaRepository $tareasCumplidas,
        private readonly EtapaLogradaRepository $etapasLogradas,
        private readonly EspecialidadProtagonistaRepository $especialidadesProtagonista,
        private readonly InsigniaOtorgadaRepository $insigniasOtorgadas,
        private readonly NomenclaturaRepository $nomenclaturas,
        private readonly AreaRepository $areas
    ) {
        parent::__construct($conexion);
    }

    public function resumen(int $idUsuario, int $idProtagonista): ResumenProgresion
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Ayudante);
        return $this->armarResumen($idProtagonista, $rama);
    }

    public function pantallaCompetencias(int $idUsuario, int $idProtagonista): PantallaCompetencias
    {
        [$rama, $nivel] = $this->cargarConNivel($idUsuario, $idProtagonista, NivelRol::Ayudante);
        $resumen = $this->armarResumen($idProtagonista, $rama);
        $elegidas = $this->competenciasPorId($idProtagonista);
        
        $accionesPorCompetencia = [];
        if ($rama->usaAcciones) {
            $cumplidas = [];
            foreach ($this->accionesCumplidas->listarPorProtagonista($idProtagonista) as $ac) {
                $cumplidas[$ac->idAccion] = true;
            }
            foreach ($this->acciones->listarPorRama($rama->id) as $accion) {
                $accionesPorCompetencia[$accion->idCompetencia][] = new AccionEnPantalla($accion, isset($cumplidas[$accion->id]));
            }
        }
        
        $competenciasPorArea = [];
        foreach ($this->competencias->listarPorRama($rama->id) as $competencia) {
            $elegida = $elegidas[$competencia->id] ?? null;
            $competenciasPorArea[$competencia->idArea][] = new CompetenciaEnPantalla(
                competencia: $competencia,
                estado: $elegida?->estado,
                idEtapa: $elegida?->idEtapa,
                acciones: $accionesPorCompetencia[$competencia->id] ?? [],
            );
        }
        
        $areas = array_map(
            fn (ContadorArea $contador) => new AreaEnPantalla($contador, $competenciasPorArea[$contador->idArea] ?? []),
            $resumen->areas,
        );
        
        $nombresEtapas = [];
        foreach ($this->etapas->listarPorRama($rama->id) as $etapa) {
            $nombresEtapas[(int) $etapa->id] = $etapa->titulo;
        }
        
        return new PantallaCompetencias(
            resumen: $resumen,
            areas: $areas,
            nombresEtapas: $nombresEtapas,
            terminos: array_map(fn ($n) => $n->texto, $this->nomenclaturas->listarPorRama($rama->id)),
            puedeEditar: $nivel->value >= NivelRol::Responsable->value,
            puedeQuitar: $nivel->value >= NivelRol::Jefe->value,
        );
    }

    public function pantallaEtapas(int $idUsuario, int $idProtagonista): PantallaEtapas
    {
        [$rama, $nivel] = $this->cargarConNivel($idUsuario, $idProtagonista, NivelRol::Ayudante);
        [$actual, $enCurso, $todas] = $this->situacionEtapas($idProtagonista, $rama);
        
        $logradas = [];
        foreach ($this->etapasLogradas->listarPorProtagonista($idProtagonista) as $lograda) {
            $logradas[$lograda->idEtapa] = $lograda;
        }
        
        $tareasCumplidas = [];
        foreach ($this->tareasCumplidas->listarPorProtagonista($idProtagonista) as $cumplida) {
            $tareasCumplidas[$cumplida->idTareaEtapa] = $cumplida->fecha;
        }
        
        $etapas = array_map(fn (Etapa $etapa) => new EtapaEnPantalla(
            etapa: $etapa,
            lograda: $logradas[$etapa->id] ?? null,
            enCurso: $enCurso?->id === $etapa->id,
            esLaActual: $actual?->id === $etapa->id,
            tareas: array_map(
                fn ($tarea) => new TareaEnPantalla($tarea, $tareasCumplidas[$tarea->id] ?? null),
                $this->tareas->listarPorEtapa((int) $etapa->id),
            ),
        ), $todas);
        
        $otorgadas = [];
        foreach ($this->insigniasOtorgadas->listarPorProtagonista($idProtagonista) as $otorgada) {
            $otorgadas[$otorgada->idInsignia] = $otorgada->fecha;
        }
        
        $insignias = array_map(
            fn ($insignia) => new InsigniaEnPantalla($insignia, $otorgadas[$insignia->id] ?? null),
            $this->insignias->listarParaRama($rama->id),
        );
        
        $especialidades = [];
        if ($rama->usaEspecialidades) {
            $suyas = [];
            foreach ($this->especialidadesProtagonista->listarPorProtagonista($idProtagonista) as $ep) {
                $suyas[$ep->idEspecialidad] = $ep;
            }
            $porArea = [];
            foreach ($this->especialidades->listarTodas() as $especialidad) {
                $suya = $suyas[$especialidad->id] ?? null;
                $porArea[$especialidad->idArea][] = new EspecialidadEnPantalla($especialidad, $suya?->estado, $suya?->fecha);
            }
            foreach ($this->areas->listarTodas() as $area) {
                if (isset($porArea[$area->id])) {
                    $especialidades[] = ['area' => $area, 'especialidades' => $porArea[$area->id]];
                }
            }
        }
        
        return new PantallaEtapas(
            resumen: $this->armarResumen($idProtagonista, $rama),
            etapas: $etapas,
            insignias: $insignias,
            especialidades: $especialidades,
            pideElemento: $rama->id === self::ID_RAMA_CAMINANTES,
            puedeEditar: $nivel->value >= NivelRol::Responsable->value,
            puedeQuitar: $nivel->value >= NivelRol::Jefe->value,
        );
    }

    private function armarResumen(int $idProtagonista, Rama $rama): ResumenProgresion
    {
        [$actual, $enCurso] = $this->situacionEtapas($idProtagonista, $rama);
        $elegidas = $this->competenciasPorId($idProtagonista);
        
        $porArea = [];
        $desarrolladasTotal = 0;
        $catalogo = $this->competencias->listarPorRama($rama->id);
        
        foreach ($catalogo as $competencia) {
            $porArea[$competencia->idArea] ??= ['desarrolladas' => 0, 'total' => 0];
            $porArea[$competencia->idArea]['total']++;
            if (($elegidas[$competencia->id] ?? null)?->estado === EstadoProgresion::Desarrollada) {
                $porArea[$competencia->idArea]['desarrolladas']++;
                $desarrolladasTotal++;
            }
        }
        
        $areas = [];
        foreach ($this->areasRama->listarPorRama($rama->id) as $areaRama) {
            $areas[] = new ContadorArea(
                idArea: $areaRama->idArea,
                titulo: $areaRama->titulo,
                desarrolladas: $porArea[$areaRama->idArea]['desarrolladas'] ?? 0,
                total: $porArea[$areaRama->idArea]['total'] ?? 0,
            );
        }
        
        $insigniaPendiente = null;
        if ($enCurso !== null) {
            $insignia = $this->insignias->buscarPorEtapa($enCurso->id);
            if ($insignia !== null && $this->insigniasOtorgadas->buscar($idProtagonista, $insignia->id) === null) {
                $insigniaPendiente = $insignia;
            }
        }
        
        return new ResumenProgresion(
            rama: $rama,
            etapaActual: $actual,
            etapaEnCurso: $enCurso,
            enCursoElegidas: $enCurso === null ? 0 : $this->contarEnEtapa($elegidas, $enCurso->id, false),
            enCursoDesarrolladas: $enCurso === null ? 0 : $this->contarEnEtapa($elegidas, $enCurso->id, true),
            desarrolladasTotal: $desarrolladasTotal,
            competenciasTotal: count($catalogo),
            areas: $areas,
            adeudado: $enCurso === null ? [] : $this->adeudado($idProtagonista, $enCurso),
            insigniaPendiente: $insigniaPendiente,
        );
    }

    public function avanzarCompetencia(
        int $idUsuario,
        int $idProtagonista,
        int $idCompetencia,
        bool $confirmarIgual = false,
        ?string $fecha = null,
    ): ResultadoProgresion {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $competencia = $this->competenciaDeRama($idCompetencia, $rama);
        $fecha = $this->fecha($fecha);
        $elegida = $this->competenciasProtagonista->buscar($idProtagonista, $idCompetencia);
        
        if ($elegida === null) {
            $this->elegirCompetencia($idProtagonista, $competencia, $rama, $confirmarIgual, $fecha);
            return new ResultadoProgresion();
        }
        
        if ($elegida->estado === EstadoProgresion::EnProceso) {
            return $this->desarrollarCompetencia($idProtagonista, $competencia, $elegida, $rama, $confirmarIgual, $fecha);
        }
        
        throw new ReglaNegocioException('La competencia ya está desarrollada.');
    }

    public function retrocederCompetencia(int $idUsuario, int $idProtagonista, int $idCompetencia): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $this->competenciaDeRama($idCompetencia, $rama);
        $elegida = $this->competenciasProtagonista->buscar($idProtagonista, $idCompetencia)
            ?? throw new ReglaNegocioException('La competencia ya está pendiente.');
            
        if ($elegida->estado === EstadoProgresion::Desarrollada) {
            $elegida->estado = EstadoProgresion::EnProceso;
            $elegida->fecha = $this->fecha(null);
            $this->competenciasProtagonista->actualizar($elegida);
            return;
        }
        $this->competenciasProtagonista->eliminar($idProtagonista, $idCompetencia);
    }

    public function marcarAccion(int $idUsuario, int $idProtagonista, int $idAccion, ?string $fecha = null): ResultadoProgresion
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $accion = $this->accionDeRama($idAccion, $rama);
        $competencia = $this->competenciaDeRama($accion->idCompetencia, $rama);
        $fecha = $this->fecha($fecha);
        $elegida = $this->competenciasProtagonista->buscar($idProtagonista, $competencia->id)
            ?? throw new ReglaNegocioException('Primero hay que elegir la competencia (pasarla a En proceso).');
            
        if ($this->accionesCumplidas->buscar($idProtagonista, $idAccion) !== null) {
            return new ResultadoProgresion();
        }
        
        $this->accionesCumplidas->insertar(new AccionCumplida($idProtagonista, $idAccion, $fecha));
        $sugerencias = [];
        
        if (
            $elegida->estado === EstadoProgresion::EnProceso
            && !$accion->esOpcional
            && $this->accionesRequeridasFaltantes($idProtagonista, $competencia, $rama) === []
        ) {
            $sugerencias[] = new Aviso(
                TipoAviso::CompetenciaLista,
                'Cumplió todas las acciones requeridas. ¿Dar la competencia por desarrollada?',
            );
        }
        return new ResultadoProgresion($sugerencias);
    }

    public function desmarcarAccion(int $idUsuario, int $idProtagonista, int $idAccion): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $this->accionDeRama($idAccion, $rama);
        $this->accionesCumplidas->eliminar($idProtagonista, $idAccion);
    }

    public function marcarTarea(int $idUsuario, int $idProtagonista, int $idTareaEtapa, ?string $fecha = null): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $this->tareaDeRama($idTareaEtapa, $rama);
        $fecha = $this->fecha($fecha);
        if ($this->tareasCumplidas->buscar($idProtagonista, $idTareaEtapa) === null) {
            $this->tareasCumplidas->insertar(new TareaEtapaCumplida($idProtagonista, $idTareaEtapa, $fecha));
        }
    }

    public function desmarcarTarea(int $idUsuario, int $idProtagonista, int $idTareaEtapa): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $this->tareaDeRama($idTareaEtapa, $rama);
        $this->tareasCumplidas->eliminar($idProtagonista, $idTareaEtapa);
    }

    public function confirmarEtapa(
        int $idUsuario,
        int $idProtagonista,
        int $idEtapa,
        ?Elemento $elemento = null,
        bool $confirmarIgual = false,
        ?string $fecha = null,
    ): void {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $etapa = $this->etapaDeRama($idEtapa, $rama);
        $fecha = $this->fecha($fecha);
        
        if ($this->etapasLogradas->buscar($idProtagonista, $idEtapa) !== null) {
            throw new ReglaNegocioException("La etapa {$etapa->titulo} ya está lograda.");
        }
        
        foreach ($this->etapas->listarPorRama($rama->id) as $otra) {
            if ($otra->numero === $etapa->numero - 1 && $this->etapasLogradas->buscar($idProtagonista, $otra->id) === null) {
                throw new ReglaNegocioException("Primero hay que confirmar la etapa {$otra->titulo}.");
            }
        }
        
        if ($rama->id === self::ID_RAMA_CAMINANTES && $elemento === null) {
            throw new ReglaNegocioException('En Caminantes hay que elegir el elemento de la etapa.');
        }
        if ($rama->id !== self::ID_RAMA_CAMINANTES && $elemento !== null) {
            throw new ReglaNegocioException('Solo en Caminantes se elige un elemento.');
        }
        
        $this->exigirConfirmacion($this->adeudado($idProtagonista, $etapa), $confirmarIgual);
        $this->etapasLogradas->insertar(new EtapaLograda($idProtagonista, $idEtapa, $elemento, $fecha));
    }

    public function quitarEtapa(int $idUsuario, int $idProtagonista, int $idEtapa): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Jefe);
        $etapa = $this->etapaDeRama($idEtapa, $rama);
        if ($this->etapasLogradas->buscar($idProtagonista, $idEtapa) === null) {
            throw new ReglaNegocioException("La etapa {$etapa->titulo} no está lograda.");
        }
        [$actual] = $this->situacionEtapas($idProtagonista, $rama);
        if ($actual?->id !== $idEtapa) {
            throw new ReglaNegocioException("Solo se puede quitar la última etapa lograda ({$actual?->titulo}).");
        }
        $this->etapasLogradas->eliminar($idProtagonista, $idEtapa);
    }

    public function otorgarInsignia(int $idUsuario, int $idProtagonista, int $idInsignia, ?string $fecha = null): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $insignia = $this->insignias->buscarPorId($idInsignia)
            ?? throw new NoEncontradoException('La insignia no existe.');
        if ($insignia->idRama !== null && $insignia->idRama !== $rama->id) {
            throw new ReglaNegocioException("La insignia {$insignia->titulo} no es de {$rama->titulo}.");
        }
        $fecha = $this->fecha($fecha);
        if ($this->insigniasOtorgadas->buscar($idProtagonista, $idInsignia) !== null) {
            throw new ReglaNegocioException("Ya recibió la insignia {$insignia->titulo}.");
        }
        $this->insigniasOtorgadas->insertar(new InsigniaOtorgada($idProtagonista, $idInsignia, $fecha));
    }

    public function quitarInsignia(int $idUsuario, int $idProtagonista, int $idInsignia): void
    {
        $this->cargar($idUsuario, $idProtagonista, NivelRol::Jefe);
        if ($this->insigniasOtorgadas->buscar($idProtagonista, $idInsignia) === null) {
            throw new ReglaNegocioException('El protagonista no tiene esa insignia.');
        }
        $this->insigniasOtorgadas->eliminar($idProtagonista, $idInsignia);
    }

    public function avanzarEspecialidad(int $idUsuario, int $idProtagonista, int $idEspecialidad, ?string $fecha = null): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $this->especialidadDeRama($idEspecialidad, $rama);
        $fecha = $this->fecha($fecha);
        $actual = $this->especialidadesProtagonista->buscar($idProtagonista, $idEspecialidad);
        if ($actual === null) {
            $this->especialidadesProtagonista->insertar(
                new EspecialidadProtagonista($idProtagonista, $idEspecialidad, EstadoProgresion::EnProceso, $fecha)
            );
            return;
        }
        if ($actual->estado === EstadoProgresion::Desarrollada) {
            throw new ReglaNegocioException('La especialidad ya está desarrollada.');
        }
        $actual->estado = EstadoProgresion::Desarrollada;
        $actual->fecha = $fecha;
        $this->especialidadesProtagonista->actualizar($actual);
    }

    public function retrocederEspecialidad(int $idUsuario, int $idProtagonista, int $idEspecialidad): void
    {
        $rama = $this->cargar($idUsuario, $idProtagonista, NivelRol::Responsable);
        $this->especialidadDeRama($idEspecialidad, $rama);
        $actual = $this->especialidadesProtagonista->buscar($idProtagonista, $idEspecialidad)
            ?? throw new ReglaNegocioException('La especialidad ya está pendiente.');
        if ($actual->estado === EstadoProgresion::Desarrollada) {
            $actual->estado = EstadoProgresion::EnProceso;
            $actual->fecha = $this->fecha(null);
            $this->especialidadesProtagonista->actualizar($actual);
            return;
        }
        $this->especialidadesProtagonista->eliminar($idProtagonista, $idEspecialidad);
    }

    private function elegirCompetencia(
        int $idProtagonista,
        Competencia $competencia,
        Rama $rama,
        bool $confirmarIgual,
        string $fecha,
    ): void {
        [, $enCurso, $todas] = $this->situacionEtapas($idProtagonista, $rama);
        $avisos = [];
        if ($enCurso === null) {
            $destino = end($todas) ?: throw new \LogicException("La rama {$rama->id} no tiene etapas.");
            $avisos[] = new Aviso(
                TipoAviso::SinEtapaEnCurso,
                "Ya logró todas las etapas. La competencia se va a sumar a {$destino->titulo}, por encima de su cupo.",
            );
        } else {
            $destino = $enCurso;
            $elegidas = $this->contarEnEtapa($this->competenciasPorId($idProtagonista), $destino->id, false);
            if ($elegidas >= $destino->competenciasEtapa) {
                $avisos[] = new Aviso(
                    TipoAviso::CupoExcedido,
                    "La etapa {$destino->titulo} ya tiene {$elegidas} de {$destino->competenciasEtapa} competencias elegidas.",
                );
            }
        }
        $this->exigirConfirmacion($avisos, $confirmarIgual);
        $this->competenciasProtagonista->insertar(new CompetenciaProtagonista(
            idProtagonista: $idProtagonista,
            idCompetencia: $competencia->id,
            idEtapa: $destino->id,
            estado: EstadoProgresion::EnProceso,
            fecha: $fecha,
        ));
    }

    private function desarrollarCompetencia(
        int $idProtagonista,
        Competencia $competencia,
        CompetenciaProtagonista $elegida,
        Rama $rama,
        bool $confirmarIgual,
        string $fecha,
    ): ResultadoProgresion {
        $faltantes = $this->accionesRequeridasFaltantes($idProtagonista, $competencia, $rama);
        if ($faltantes !== []) {
            $numeros = implode(', ', array_map(fn (Accion $a) => $a->numero, $faltantes));
            $this->exigirConfirmacion([new Aviso(
                TipoAviso::AccionesPendientes,
                'Le faltan ' . count($faltantes) . " acciones requeridas (n.º {$numeros}).",
            )], $confirmarIgual);
        }
        $elegida->estado = EstadoProgresion::Desarrollada;
        $elegida->fecha = $fecha;
        $this->competenciasProtagonista->actualizar($elegida);
        
        $sugerencias = [];
        [, $enCurso] = $this->situacionEtapas($idProtagonista, $rama);
        if ($enCurso !== null && $elegida->idEtapa === $enCurso->id) {
            $desarrolladas = $this->contarEnEtapa($this->competenciasPorId($idProtagonista), $enCurso->id, true);
            if ($desarrolladas >= $enCurso->competenciasEtapa) {
                $sugerencias[] = new Aviso(
                    TipoAviso::EtapaLista,
                    "Completó las {$enCurso->competenciasEtapa} competencias de la etapa {$enCurso->titulo}. ¿Confirmar la etapa?",
                );
            }
        }
        return new ResultadoProgresion($sugerencias);
    }

    private function situacionEtapas(int $idProtagonista, Rama $rama): array
    {
        $todas = $this->etapas->listarPorRama($rama->id);
        $lograda = $this->etapasLogradas->buscarActual($idProtagonista, $rama->id);
        $actual = null;
        $enCurso = null;
        foreach ($todas as $etapa) {
            if ($lograda !== null && $etapa->id === $lograda->idEtapa) {
                $actual = $etapa;
            }
        }
        $numeroActual = $actual?->numero ?? 0;
        foreach ($todas as $etapa) {
            if ($etapa->numero > $numeroActual) {
                $enCurso = $etapa;
                break;
            }
        }
        return [$actual, $enCurso, $todas];
    }

    private function adeudado(int $idProtagonista, Etapa $etapa): array
    {
        $avisos = [];
        $desarrolladas = $this->contarEnEtapa($this->competenciasPorId($idProtagonista), $etapa->id, true);
        if ($desarrolladas < $etapa->competenciasEtapa) {
            $avisos[] = new Aviso(
                TipoAviso::CupoIncompleto,
                "Tiene {$desarrolladas} de {$etapa->competenciasEtapa} competencias desarrolladas en la etapa {$etapa->titulo}.",
            );
        }
        $cumplidas = [];
        foreach ($this->tareasCumplidas->listarPorProtagonistaYEtapa($idProtagonista, $etapa->id) as $tc) {
            $cumplidas[$tc->idTareaEtapa] = true;
        }
        foreach ($this->tareas->listarPorEtapa($etapa->id) as $tarea) {
            if (!isset($cumplidas[$tarea->id])) {
                $avisos[] = new Aviso(TipoAviso::TareaPendiente, "Falta la tarea {$tarea->numero}: {$tarea->descripcion}");
            }
        }
        return $avisos;
    }

    private function accionesRequeridasFaltantes(int $idProtagonista, Competencia $competencia, Rama $rama): array
    {
        if (!$rama->usaAcciones) {
            return [];
        }
        $cumplidas = [];
        foreach ($this->accionesCumplidas->listarPorProtagonistaYCompetencia($idProtagonista, $competencia->id) as $ac) {
            $cumplidas[$ac->idAccion] = true;
        }
        return array_values(array_filter(
            $this->acciones->listarPorCompetencia($competencia->id),
            fn (Accion $a) => !$a->esOpcional && !isset($cumplidas[$a->id]),
        ));
    }

    private function competenciasPorId(int $idProtagonista): array
    {
        $porId = [];
        foreach ($this->competenciasProtagonista->listarPorProtagonista($idProtagonista) as $cp) {
            $porId[$cp->idCompetencia] = $cp;
        }
        return $porId;
    }

    private function contarEnEtapa(array $elegidas, int $idEtapa, bool $soloDesarrolladas): int
    {
        return count(array_filter(
            $elegidas,
            fn (CompetenciaProtagonista $cp) => $cp->idEtapa === $idEtapa
                && (!$soloDesarrolladas || $cp->estado === EstadoProgresion::Desarrollada),
        ));
    }

    private function cargar(int $idUsuario, int $idProtagonista, NivelRol $minimo): Rama
    {
        return $this->cargarConNivel($idUsuario, $idProtagonista, $minimo)[0];
    }

    private function cargarConNivel(int $idUsuario, int $idProtagonista, NivelRol $minimo): array
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');
        $nivel = $this->permisos->nivelEnSeccion($idUsuario, $protagonista->idSeccion)
            ?? throw new NoEncontradoException('El protagonista no existe.');
        if ($nivel->value < $minimo->value) {
            throw new AccesoDenegadoException('Necesitás el rol ' . $minimo->etiqueta() . ' en esta sección para hacer esto.');
        }
        $seccion = $this->secciones->buscarPorId($protagonista->idSeccion)
            ?? throw new \LogicException("La sección {$protagonista->idSeccion} no existe.");
        $rama = $this->ramas->buscarPorId($seccion->idRama)
            ?? throw new \LogicException("La rama {$seccion->idRama} no existe.");
        return [$rama, $nivel];
    }

    private function exigirConfirmacion(array $avisos, bool $confirmarIgual): void
    {
        if ($avisos !== [] && !$confirmarIgual) {
            throw new RequisitosPendientesException($avisos);
        }
    }

    private function competenciaDeRama(int $idCompetencia, Rama $rama): Competencia
    {
        $competencia = $this->competencias->buscarPorId($idCompetencia)
            ?? throw new NoEncontradoException('La competencia no existe.');
        if ($competencia->idRama !== $rama->id) {
            throw new ReglaNegocioException("La competencia no es de {$rama->titulo}.");
        }
        return $competencia;
    }

    private function accionDeRama(int $idAccion, Rama $rama): Accion
    {
        if (!$rama->usaAcciones) {
            throw new ReglaNegocioException("{$rama->titulo} no usa acciones.");
        }
        $accion = $this->acciones->buscarPorId($idAccion)
            ?? throw new NoEncontradoException('La acción no existe.');
        $this->competenciaDeRama($accion->idCompetencia, $rama);
        return $accion;
    }

    private function etapaDeRama(int $idEtapa, Rama $rama): Etapa
    {
        $etapa = $this->etapas->buscarPorId($idEtapa)
            ?? throw new NoEncontradoException('La etapa no existe.');
        if ($etapa->idRama !== $rama->id) {
            throw new ReglaNegocioException("La etapa no es de {$rama->titulo}.");
        }
        return $etapa;
    }

    private function tareaDeRama(int $idTareaEtapa, Rama $rama): void
    {
        $tarea = $this->tareas->buscarPorId($idTareaEtapa)
            ?? throw new NoEncontradoException('La tarea no existe.');
        $this->etapaDeRama($tarea->idEtapa, $rama);
    }

    private function especialidadDeRama(int $idEspecialidad, Rama $rama): Especialidad
    {
        if (!$rama->usaEspecialidades) {
            throw new ReglaNegocioException("{$rama->titulo} no usa especialidades.");
        }
        return $this->especialidades->buscarPorId($idEspecialidad)
            ?? throw new NoEncontradoException('La especialidad no existe.');
    }

    private function fecha(?string $fecha): string
    {
        $hoy = date('Y-m-d');
        if ($fecha === null) {
            return $hoy;
        }
        $fecha = Validacion::fecha($fecha);
        if ($fecha > $hoy) {
            throw new ReglaNegocioException('La fecha no puede ser posterior a hoy.');
        }
        return $fecha;
    }
}