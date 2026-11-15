<?php // src/Service/ProtagonistaService.php
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

    public function nominaPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante, 'La sección no existe.');
        return $this->protagonistas->listarNomina([$idSeccion]);
    }

    public function listarPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Ayudante, 'La sección no existe.');
        return $this->protagonistas->listarFichasPorSeccion($idSeccion);
    }

    public function buscarFicha(int $idUsuario, int $idProtagonista): FichaProtagonista
    {
        $ficha = $this->protagonistas->buscarFicha($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $ficha->protagonista->idSeccion, NivelRol::Ayudante, 'El protagonista no existe.');
        return $ficha;
    }

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

    public function actualizar(int $idUsuario, Persona $persona, Protagonista $protagonista): void
    {
        $guardado = $this->protagonistas->buscarPorId($this->idDe($protagonista))
            ?? throw new NoEncontradoException('El protagonista no existe.');
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $guardado->idSeccion, NivelRol::Responsable, 'El protagonista no existe.');
        if ($protagonista->idSeccion !== $guardado->idSeccion) {
            throw new ReglaNegocioException('Para pasar al protagonista a otra sección usá "Cambiar de sección".');
        }
        $protagonista->idPersona = $guardado->idPersona;
        $persona->id = $guardado->idPersona;
        $this->validarPersona($persona, $guardado->idPersona);
        $this->validarProtagonista($protagonista);
        $this->transaccion(function () use ($persona, $protagonista): void {
            $this->personas->actualizar($persona);
            $this->protagonistas->actualizar($protagonista);
        });
    }

    public function cambiarSeccion(int $idUsuario, int $idProtagonista, int $idSeccionDestino): void
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');
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
        $protagonista->idPatrulla = null;
        $this->protagonistas->actualizar($protagonista);
    }

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

    private function validarPersona(Persona $persona, ?int $idPersonaPropia): void
    {
        Validacion::persona($persona);
        $conEseDni = $this->personas->buscarPorDni($persona->dni);
        if ($conEseDni !== null && $conEseDni->id !== $idPersonaPropia) {
            throw new ReglaNegocioException('Ya existe una persona con ese DNI.');
        }
    }

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

    private function idDe(Protagonista $protagonista): int
    {
        return $protagonista->id
            ?? throw new \InvalidArgumentException('Para actualizar hace falta el id del protagonista.');
    }
}