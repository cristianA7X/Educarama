<?php // src/Service/EventoService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Evento;
use Educarama\Model\EventoSeccion;
use Educarama\Model\Seccion;
use Educarama\Repository\EventoRepository;
use Educarama\Repository\EventoSeccionRepository;
use Educarama\Repository\GrupoRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Vista\EventoConSecciones;
use Educarama\Service\Vista\GrupoEnPantalla;
use Educarama\Service\Vista\EventoEnPantalla;
use Educarama\Service\Vista\EventosEnPantalla;
use Educarama\Service\Vista\EventosGenerales;
use Educarama\Service\Vista\OpcionesEvento;
use PDO;

final class EventoService extends ServicioBase
{
    private const LARGO_TITULO = 200;
    private const LARGO_DESCRIPCION = 16383;

    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly EventoRepository $eventos,
        private readonly EventoSeccionRepository $eventoSecciones,
        private readonly GrupoRepository $grupos,
        private readonly SeccionRepository $secciones,
        private readonly SeccionService $seccionService
    ) {
        parent::__construct($conexion);
    }

    public function listarPorGrupo(int $idUsuario, int $idGrupo, bool $incluirInactivos = false): array
    {
        $nivel = $this->exigirRolDeGrupo($idUsuario, $idGrupo);
        $this->exigirPuedeVerInactivos($nivel, $incluirInactivos);
        return $this->armarListado($this->eventos->listarPorGrupo($idGrupo), $idGrupo, $nivel, $incluirInactivos, false);
    }

    public function listarPorSeccion(int $idUsuario, int $idSeccion, bool $incluirInactivos = false): array
    {
        $nivel = $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Supervisor, 'La sección no existe.');
        $this->exigirPuedeVerInactivos($nivel, $incluirInactivos);
        $idGrupo = $this->seccion($idSeccion)->idGrupo;
        return $this->armarListado($this->eventos->listarPorSeccion($idSeccion), $idGrupo, $nivel, $incluirInactivos, false);
    }

    public function proximosPorGrupo(int $idUsuario, int $idGrupo): array
    {
        $nivelGrupo = $this->exigirConocerGrupo($idUsuario, $idGrupo);
        if ($nivelGrupo !== null) {
            return $this->armarListado($this->eventos->listarPorGrupo($idGrupo), $idGrupo, $nivelGrupo, false, true);
        }
        $porId = [];
        foreach ($this->secciones->listarPorGrupo($idGrupo) as $seccion) {
            $nivel = $this->permisos->nivelEnSeccion($idUsuario, (int) $seccion->id);
            if ($nivel === null) {
                continue;
            }
            $lista = $this->armarListado($this->eventos->listarPorSeccion((int) $seccion->id), $idGrupo, $nivel, false, true);
            foreach ($lista as $e) {
                $porId[$e->evento->id] = $e;
            }
        }
        $proximos = array_values($porId);
        usort($proximos, fn (EventoConSecciones $a, EventoConSecciones $b) =>
            [$a->evento->fechaInicio, $a->evento->id] <=> [$b->evento->fechaInicio, $b->evento->id]);
        return $proximos;
    }

    public function proximosPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $nivel = $this->permisos->exigirVisibleEnSeccion($idUsuario, $idSeccion, NivelRol::Supervisor, 'La sección no existe.');
        $idGrupo = $this->seccion($idSeccion)->idGrupo;
        return $this->armarListado($this->eventos->listarPorSeccion($idSeccion), $idGrupo, $nivel, false, true);
    }

    public function buscar(int $idUsuario, int $idEvento): EventoConSecciones
    {
        return $this->cargarVisible($idUsuario, $idEvento);
    }

    public function paraEditar(int $idUsuario, int $idEvento): EventoConSecciones
    {
        $actual = $this->cargarVisible($idUsuario, $idEvento);
        $this->exigirParaEscribir($idUsuario, $actual->evento->idGrupo, $actual->idSecciones, NivelRol::Responsable);
        return $actual;
    }

    public function pantallaGrupo(int $idUsuario, int $idGrupo, bool $incluirInactivos = false): EventosEnPantalla
    {
        $lista = $this->listarPorGrupo($idUsuario, $idGrupo, $incluirInactivos);
        $nivel = $this->permisos->nivelEnGrupo($idUsuario, $idGrupo)
            ?? throw new \LogicException('listarPorGrupo ya exigió un rol de grupo.');
        return $this->armarPantalla($idUsuario, $idGrupo, $lista, $nivel, $incluirInactivos);
    }

    public function pantallaSeccion(int $idUsuario, int $idSeccion, bool $incluirInactivos = false): EventosEnPantalla
    {
        $lista = $this->listarPorSeccion($idUsuario, $idSeccion, $incluirInactivos);
        $nivel = $this->permisos->nivelEnSeccion($idUsuario, $idSeccion)
            ?? throw new \LogicException('listarPorSeccion ya exigió acceso a la sección.');
        return $this->armarPantalla($idUsuario, $this->seccion($idSeccion)->idGrupo, $lista, $nivel, $incluirInactivos);
    }

    public function eventosGenerales(int $idUsuario): EventosGenerales
    {
        $grupos = $this->seccionService->misGrupos($idUsuario);
        $porGrupo = [];
        foreach ($grupos as $grupoPantalla) {
            $idGrupo = (int) $grupoPantalla->grupo->id;
            if ($grupoPantalla->tieneRolDeGrupo()) {
                $pantalla = $this->pantallaGrupo($idUsuario, $idGrupo);
            } else {
                $pantalla = $this->pantallaDesdeSeccionesDelUsuario($idUsuario, $idGrupo, $grupoPantalla);
            }
            $porGrupo[] = ['grupo' => $grupoPantalla, 'pantalla' => $pantalla];
        }
        return new EventosGenerales($porGrupo);
    }

    private function pantallaDesdeSeccionesDelUsuario(int $idUsuario, int $idGrupo, GrupoEnPantalla $grupoPantalla): EventosEnPantalla
    {
        $porId = [];
        $nivelMayor = null;
        foreach ($grupoPantalla->secciones as $secPantalla) {
            $idSeccion = (int) $secPantalla->seccion->id;
            $nivel = $this->permisos->nivelEnSeccion($idUsuario, $idSeccion);
            if ($nivel === null) {
                continue;
            }
            if ($nivelMayor === null || $nivel->value > $nivelMayor->value) {
                $nivelMayor = $nivel;
            }
            $lista = $this->armarListado(
                $this->eventos->listarPorSeccion($idSeccion),
                $idGrupo, $nivel, false, false
            );
            foreach ($lista as $e) {
                $porId[$e->evento->id] = $e;
            }
        }
        $unicos = array_values($porId);
        usort($unicos, fn (EventoConSecciones $a, EventoConSecciones $b) =>
            [$a->evento->fechaInicio, $a->evento->id] <=> [$b->evento->fechaInicio, $b->evento->id]);
        
        $nombres = [];
        foreach ($this->secciones->listarPorGrupo($idGrupo) as $seccion) {
            $nombres[(int) $seccion->id] = $seccion->nombre;
        }
        $eventosEnPantalla = array_map(fn (EventoConSecciones $e) => new EventoEnPantalla(
            evento: $e,
            puedeEditar: $this->puedeEscribir($idUsuario, $idGrupo, $e->idSecciones, NivelRol::Responsable),
            puedeEliminar: $this->puedeEscribir($idUsuario, $idGrupo, $e->idSecciones, NivelRol::Jefe),
        ), $unicos);
        return new EventosEnPantalla(
            eventos: $eventosEnPantalla,
            nombresSecciones: $nombres,
            puedeCrear: $this->calcularOpciones($idUsuario, $idGrupo)->hayAlgunaOpcion(),
            puedeVerInactivos: $nivelMayor !== null && $nivelMayor->value >= NivelRol::Responsable->value,
            incluyeInactivos: false,
        );
    }

    public function opcionesFormulario(int $idUsuario, int $idGrupo): OpcionesEvento
    {
        $this->exigirConocerGrupo($idUsuario, $idGrupo);
        $opciones = $this->calcularOpciones($idUsuario, $idGrupo);
        if (!$opciones->hayAlgunaOpcion()) {
            throw new AccesoDenegadoException('Necesitás el rol Responsable en el grupo o en alguna de sus secciones para cargar eventos.');
        }
        return $opciones;
    }

    public function crear(int $idUsuario, Evento $evento, array $idSecciones): int
    {
        $this->exigirConocerGrupo($idUsuario, $evento->idGrupo);
        $idSecciones = $this->normalizarSecciones($evento->idGrupo, $idSecciones);
        $this->exigirParaEscribir($idUsuario, $evento->idGrupo, $idSecciones, NivelRol::Responsable);
        $this->validar($evento, null);
        $evento->id = null;
        $evento->activo = true;
        $evento->idTipoEvento = null;
        return $this->transaccion(function () use ($evento, $idSecciones) {
            $id = $this->eventos->insertar($evento);
            $this->guardarSecciones($id, $idSecciones);
            return $id;
        });
    }

    public function actualizar(int $idUsuario, Evento $evento, array $idSecciones): void
    {
        $actual = $this->cargarVisible($idUsuario, (int) $evento->id);
        $idGrupo = $actual->evento->idGrupo;
        $this->exigirParaEscribir($idUsuario, $idGrupo, $actual->idSecciones, NivelRol::Responsable);
        $idSecciones = $this->normalizarSecciones($idGrupo, $idSecciones);
        $this->exigirParaEscribir($idUsuario, $idGrupo, $idSecciones, NivelRol::Responsable);
        
        $evento->idGrupo = $idGrupo;
        $evento->activo = $actual->evento->activo;
        $evento->idTipoEvento = null;
        $this->validar($evento, $actual->evento);
        $this->transaccion(function () use ($evento, $idSecciones) {
            $this->eventos->actualizar($evento);
            $this->eventoSecciones->eliminarPorEvento((int) $evento->id);
            $this->guardarSecciones((int) $evento->id, $idSecciones);
        });
    }

    public function desactivar(int $idUsuario, int $idEvento): void
    {
        $this->cambiarActivo($idUsuario, $idEvento, false);
    }

    public function reactivar(int $idUsuario, int $idEvento): void
    {
        $this->cambiarActivo($idUsuario, $idEvento, true);
    }

    public function eliminar(int $idUsuario, int $idEvento): void
    {
        $actual = $this->cargarVisible($idUsuario, $idEvento);
        $this->exigirParaEscribir($idUsuario, $actual->evento->idGrupo, $actual->idSecciones, NivelRol::Jefe);
        $this->eventos->eliminar($idEvento);
    }

    private function cargarVisible(int $idUsuario, int $idEvento): EventoConSecciones
    {
        $noEncontrado = new NoEncontradoException('El evento no existe.');
        $evento = $this->eventos->buscarPorId($idEvento) ?? throw $noEncontrado;
        $idSecciones = $this->idsDeSecciones($this->eventoSecciones->listarPorEvento($idEvento));
        $nivel = $this->nivelParaVer($idUsuario, $evento, $idSecciones);
        if ($nivel === null || !$this->esVisible($evento, $nivel, true)) {
            throw $noEncontrado;
        }
        return new EventoConSecciones($evento, $idSecciones);
    }

    private function nivelParaVer(int $idUsuario, Evento $evento, array $idSecciones): ?NivelRol
    {
        $niveles = [$this->permisos->nivelEnGrupo($idUsuario, $evento->idGrupo)];
        $seccionesDelEvento = $idSecciones !== []
            ? $idSecciones
            : array_map(fn ($s) => (int) $s->id, $this->secciones->listarPorGrupo($evento->idGrupo));
        foreach ($seccionesDelEvento as $idSeccion) {
            $niveles[] = $this->permisos->nivelEnSeccion($idUsuario, $idSeccion);
        }
        $mayor = null;
        foreach ($niveles as $nivel) {
            if ($nivel !== null && ($mayor === null || $nivel->value > $mayor->value)) {
                $mayor = $nivel;
            }
        }
        return $mayor;
    }

    private function esVisible(Evento $evento, NivelRol $nivel, bool $incluirInactivos): bool
    {
        if ($nivel === NivelRol::Supervisor && !$evento->esPublico) {
            return false;
        }
        if (!$evento->activo) {
            return $incluirInactivos && $nivel->value >= NivelRol::Responsable->value;
        }
        return true;
    }

    private function exigirConocerGrupo(int $idUsuario, int $idGrupo): ?NivelRol
    {
        $noEncontrado = new NoEncontradoException('El grupo no existe.');
        $this->grupos->buscarPorId($idGrupo) ?? throw $noEncontrado;
        if (!$this->permisos->tieneAccesoAlGrupo($idUsuario, $idGrupo)) {
            throw $noEncontrado;
        }
        return $this->permisos->nivelEnGrupo($idUsuario, $idGrupo);
    }

    private function exigirRolDeGrupo(int $idUsuario, int $idGrupo): NivelRol
    {
        return $this->exigirConocerGrupo($idUsuario, $idGrupo)
            ?? throw new AccesoDenegadoException('Necesitás un rol en el grupo para ver sus eventos. Los de tu sección están en la pantalla de la sección.');
    }

    private function puedeEscribir(int $idUsuario, int $idGrupo, array $idSecciones, NivelRol $minimo): bool
    {
        if ($idSecciones === []) {
            return $this->permisos->puedeEnGrupo($idUsuario, $idGrupo, $minimo);
        }
        foreach ($idSecciones as $idSeccion) {
            if (!$this->permisos->puedeEnSeccion($idUsuario, $idSeccion, $minimo)) {
                return false;
            }
        }
        return true;
    }

    private function calcularOpciones(int $idUsuario, int $idGrupo): OpcionesEvento
    {
        $grupo = $this->grupos->buscarPorId($idGrupo)
            ?? throw new NoEncontradoException('El grupo no existe.');
        $secciones = array_values(array_filter(
            $this->secciones->listarPorGrupo($idGrupo),
            fn ($s) => $this->permisos->puedeEnSeccion($idUsuario, (int) $s->id, NivelRol::Responsable),
        ));
        return new OpcionesEvento(
            grupo: $grupo,
            secciones: $secciones,
            puedeTodoElGrupo: $this->permisos->puedeEnGrupo($idUsuario, $idGrupo, NivelRol::Responsable),
        );
    }

    private function armarPantalla(int $idUsuario, int $idGrupo, array $lista, NivelRol $nivel, bool $incluirInactivos): EventosEnPantalla
    {
        $nombres = [];
        foreach ($this->secciones->listarPorGrupo($idGrupo) as $seccion) {
            $nombres[(int) $seccion->id] = $seccion->nombre;
        }
        $eventos = array_map(fn (EventoConSecciones $e) => new EventoEnPantalla(
            evento: $e,
            puedeEditar: $this->puedeEscribir($idUsuario, $idGrupo, $e->idSecciones, NivelRol::Responsable),
            puedeEliminar: $this->puedeEscribir($idUsuario, $idGrupo, $e->idSecciones, NivelRol::Jefe),
        ), $lista);
        return new EventosEnPantalla(
            eventos: $eventos,
            nombresSecciones: $nombres,
            puedeCrear: $this->calcularOpciones($idUsuario, $idGrupo)->hayAlgunaOpcion(),
            puedeVerInactivos: $nivel->value >= NivelRol::Responsable->value,
            incluyeInactivos: $incluirInactivos,
        );
    }

    private function exigirPuedeVerInactivos(NivelRol $nivel, bool $incluirInactivos): void
    {
        if ($incluirInactivos && $nivel->value < NivelRol::Responsable->value) {
            throw new AccesoDenegadoException('Necesitás el rol Responsable para ver los eventos desactivados.');
        }
    }

    private function exigirParaEscribir(int $idUsuario, int $idGrupo, array $idSecciones, NivelRol $minimo): void
    {
        if ($idSecciones === []) {
            $this->permisos->exigirEnGrupo($idUsuario, $idGrupo, $minimo);
            return;
        }
        foreach ($idSecciones as $idSeccion) {
            $this->permisos->exigirEnSeccion($idUsuario, $idSeccion, $minimo);
        }
    }

    private function validar(Evento $evento, ?Evento $anterior): void
    {
        $evento->titulo = Validacion::textoOpcional($evento->titulo, self::LARGO_TITULO, 'El título')
            ?? throw new ReglaNegocioException('El título es obligatorio.');
        $evento->descripcion = Validacion::textoOpcional($evento->descripcion, self::LARGO_DESCRIPCION, 'La descripción')
            ?? throw new ReglaNegocioException('La descripción es obligatoria.');
        $evento->fechaInicio = Validacion::fecha(trim($evento->fechaInicio), 'La fecha de inicio');
        $fin = $evento->fechaFin === null ? '' : trim($evento->fechaFin);
        $evento->fechaFin = $fin === '' ? null : Validacion::fecha($fin, 'La fecha de fin');
        
        $cambiaInicio = $anterior === null || $anterior->fechaInicio !== $evento->fechaInicio;
        if ($cambiaInicio && $evento->fechaInicio < $this->hoy()) {
            throw new ReglaNegocioException('La fecha de inicio no puede ser anterior a hoy.');
        }
        if ($evento->fechaFin !== null && $evento->fechaFin < $evento->fechaInicio) {
            throw new ReglaNegocioException('La fecha de fin no puede ser anterior a la de inicio.');
        }
    }

    private function normalizarSecciones(int $idGrupo, array $idSecciones): array
    {
        $ids = array_values(array_unique(array_map('intval', $idSecciones)));
        foreach ($ids as $idSeccion) {
            $seccion = $this->secciones->buscarPorId($idSeccion);
            if ($seccion === null || $seccion->idGrupo !== $idGrupo) {
                throw new ReglaNegocioException('Una de las secciones elegidas no es de este grupo.');
            }
        }
        sort($ids);
        return $ids;
    }

    private function cambiarActivo(int $idUsuario, int $idEvento, bool $activo): void
    {
        $actual = $this->cargarVisible($idUsuario, $idEvento);
        $this->exigirParaEscribir($idUsuario, $actual->evento->idGrupo, $actual->idSecciones, NivelRol::Responsable);
        $actual->evento->activo = $activo;
        $this->eventos->actualizar($actual->evento);
    }

    private function armarListado(array $eventos, int $idGrupo, NivelRol $nivel, bool $incluirInactivos, bool $soloProximos): array
    {
        $seccionesPorEvento = [];
        foreach ($this->eventoSecciones->listarPorGrupo($idGrupo) as $fila) {
            $seccionesPorEvento[$fila->idEvento][] = $fila->idSeccion;
        }
        $hoy = $this->hoy();
        $listado = [];
        foreach ($eventos as $evento) {
            if (!$this->esVisible($evento, $nivel, $incluirInactivos)) {
                continue;
            }
            if ($soloProximos && ($evento->fechaFin ?? $evento->fechaInicio) < $hoy) {
                continue;
            }
            $listado[] = new EventoConSecciones($evento, $seccionesPorEvento[$evento->id] ?? []);
        }
        return $listado;
    }

    private function guardarSecciones(int $idEvento, array $idSecciones): void
    {
        foreach ($idSecciones as $idSeccion) {
            $this->eventoSecciones->insertar(new EventoSeccion($idEvento, $idSeccion));
        }
    }

    private function idsDeSecciones(array $filas): array
    {
        return array_map(fn (EventoSeccion $f) => $f->idSeccion, $filas);
    }

    private function hoy(): string
    {
        return date('Y-m-d');
    }

    private function seccion(int $idSeccion): Seccion
    {
        return $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');
    }
}