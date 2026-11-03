<?php
// src/Service/EventoService.php

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

/**
 * Eventos de un grupo: de todo el grupo (sin filas en evento_seccion) o de
 * algunas de sus secciones.
 *
 * Reglas (CLAUDE.md, "Eventos"):
 *  - Escribir: un evento de todo el grupo lo crea, edita, desactiva y reactiva
 *    un Responsable de grupo (o el Jefe de grupo); uno de algunas secciones,
 *    quien es Responsable en CADA una de ellas. Eliminar: lo mismo, con Jefe.
 *  - Ver: los eventos de todo el grupo, cualquiera con acceso al grupo o a
 *    alguna de sus secciones; los de algunas secciones, quien tiene acceso a
 *    una de ellas, más los roles de grupo. El Supervisor ve solo los que
 *    tienen es_publico. Los inactivos, solo Responsable en adelante.
 *  - Si no lo puede ver, 404: no se confirma que existe.
 *  - Título y descripción obligatorios. No se crea un evento que empieza antes
 *    de hoy; fecha_fin, si viene, no es anterior a fecha_inicio.
 *  - Las secciones tienen que ser del grupo del evento. Al editar se reemplaza
 *    la lista entera. id_tipo_evento queda siempre en NULL (reservado).
 */
final class EventoService extends ServicioBase
{
    // `evento.titulo` es VARCHAR(200).
    private const LARGO_TITULO = 200;

    // Lo que entra en un TEXT (65.535 bytes) con caracteres de 4 bytes de
    // utf8mb4. Es un límite técnico de la columna, no una regla de negocio.
    private const LARGO_DESCRIPCION = 16383;

    private PermisoService $permisos;
    private EventoRepository $eventos;
    private EventoSeccionRepository $eventoSecciones;
    private GrupoRepository $grupos;
    private SeccionRepository $secciones;

    public function __construct(
        ?PermisoService $permisos = null,
        ?EventoRepository $eventos = null,
        ?EventoSeccionRepository $eventoSecciones = null,
        ?GrupoRepository $grupos = null,
        ?SeccionRepository $secciones = null,
    ) {
        parent::__construct();
        $this->permisos = $permisos ?? new PermisoService();
        $this->eventos = $eventos ?? new EventoRepository();
        $this->eventoSecciones = $eventoSecciones ?? new EventoSeccionRepository();
        $this->grupos = $grupos ?? new GrupoRepository();
        $this->secciones = $secciones ?? new SeccionRepository();
    }

    // -----------------------------------------------------------------
    // Lectura
    // -----------------------------------------------------------------

    /**
     * Todos los eventos del grupo (de todo el grupo y de cada sección), por
     * fecha de inicio. Es la pantalla del grupo: hace falta un rol de grupo.
     *
     * @param bool $incluirInactivos Solo Responsable de grupo en adelante.
     * @return EventoConSecciones[]
     */
    public function listarPorGrupo(int $idUsuario, int $idGrupo, bool $incluirInactivos = false): array
    {
        $this->exigirGrupo($idGrupo);
        $nivel = $this->permisos->exigirEnGrupo($idUsuario, $idGrupo, NivelRol::Supervisor);
        $this->exigirPuedeVerInactivos($nivel, $incluirInactivos);

        return $this->armarListado($this->eventos->listarPorGrupo($idGrupo), $idGrupo, $nivel, $incluirInactivos, false);
    }

    /**
     * Los eventos que le tocan a la sección (los de todo su grupo y los suyos),
     * por fecha de inicio. Hace falta acceso a la sección.
     *
     * @param bool $incluirInactivos Solo Responsable de la sección en adelante.
     * @return EventoConSecciones[]
     */
    public function listarPorSeccion(int $idUsuario, int $idSeccion, bool $incluirInactivos = false): array
    {
        $nivel = $this->permisos->exigirEnSeccion($idUsuario, $idSeccion, NivelRol::Supervisor);
        $this->exigirPuedeVerInactivos($nivel, $incluirInactivos);
        $idGrupo = $this->seccion($idSeccion)->idGrupo;

        return $this->armarListado($this->eventos->listarPorSeccion($idSeccion), $idGrupo, $nivel, $incluirInactivos, false);
    }

    /**
     * Próximos eventos del grupo: activos y que todavía no terminaron
     * (fecha_fin, o fecha_inicio si no tiene fin, de hoy en adelante).
     *
     * @return EventoConSecciones[]
     */
    public function proximosPorGrupo(int $idUsuario, int $idGrupo): array
    {
        $this->exigirGrupo($idGrupo);
        $nivel = $this->permisos->exigirEnGrupo($idUsuario, $idGrupo, NivelRol::Supervisor);

        return $this->armarListado($this->eventos->listarPorGrupo($idGrupo), $idGrupo, $nivel, false, true);
    }

    /** @return EventoConSecciones[] Próximos eventos de la sección (mismo criterio que proximosPorGrupo). */
    public function proximosPorSeccion(int $idUsuario, int $idSeccion): array
    {
        $nivel = $this->permisos->exigirEnSeccion($idUsuario, $idSeccion, NivelRol::Supervisor);
        $idGrupo = $this->seccion($idSeccion)->idGrupo;

        return $this->armarListado($this->eventos->listarPorSeccion($idSeccion), $idGrupo, $nivel, false, true);
    }

    /** Un evento con sus secciones (detalle y formulario de edición). */
    public function buscar(int $idUsuario, int $idEvento): EventoConSecciones
    {
        return $this->cargarVisible($idUsuario, $idEvento);
    }

    // -----------------------------------------------------------------
    // Escritura
    // -----------------------------------------------------------------

    /**
     * Crea el evento en el grupo que trae el objeto y devuelve su id.
     * Siempre nace activo y sin tipo (id_tipo_evento reservado).
     *
     * @param int[] $idSecciones Vacío = evento de todo el grupo.
     */
    public function crear(int $idUsuario, Evento $evento, array $idSecciones): int
    {
        $this->exigirGrupo($evento->idGrupo);
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

    /**
     * Actualiza los datos y reemplaza la lista de secciones. Hace falta poder
     * escribir el evento como está Y como queda (ej. pasarlo de una sección a
     * todo el grupo pide Responsable en la sección y en el grupo).
     * El grupo, `activo` e id_tipo_evento no cambian por acá.
     *
     * @param int[] $idSecciones Vacío = evento de todo el grupo.
     */
    public function actualizar(int $idUsuario, Evento $evento, array $idSecciones): void
    {
        $actual = $this->cargarVisible($idUsuario, (int) $evento->id);
        $idGrupo = $actual->evento->idGrupo;
        $this->exigirParaEscribir($idUsuario, $idGrupo, $actual->idSecciones, NivelRol::Responsable);

        $idSecciones = $this->normalizarSecciones($idGrupo, $idSecciones);
        $this->exigirParaEscribir($idUsuario, $idGrupo, $idSecciones, NivelRol::Responsable);

        // Los datos que no vienen del formulario se toman de la base.
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

    /** Lo oculta de los listados sin borrarlo. */
    public function desactivar(int $idUsuario, int $idEvento): void
    {
        $this->cambiarActivo($idUsuario, $idEvento, false);
    }

    public function reactivar(int $idUsuario, int $idEvento): void
    {
        $this->cambiarActivo($idUsuario, $idEvento, true);
    }

    /** Borra el evento (Jefe). La base borra en cascada sus filas de evento_seccion. */
    public function eliminar(int $idUsuario, int $idEvento): void
    {
        $actual = $this->cargarVisible($idUsuario, $idEvento);
        $this->exigirParaEscribir($idUsuario, $actual->evento->idGrupo, $actual->idSecciones, NivelRol::Jefe);

        $this->eventos->eliminar($idEvento);
    }

    // -----------------------------------------------------------------
    // Permisos
    // -----------------------------------------------------------------

    /**
     * Busca el evento y controla que el usuario lo pueda ver. Si no existe o
     * no lo puede ver, la misma respuesta: 404.
     */
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

    /**
     * El rol más alto del usuario entre los que le dejan ver el evento: su rol
     * de grupo, y su rol en cada sección a la que le toca el evento (todas las
     * del grupo si es de todo el grupo). null si no tiene ninguno.
     *
     * @param int[] $idSecciones
     */
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

    /**
     * Si alguien con ese rol ve el evento: el Supervisor, solo los públicos;
     * los inactivos, solo Responsable en adelante (y si los pidió).
     */
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

    /** Pedir los inactivos en un listado es de Responsable en adelante. */
    private function exigirPuedeVerInactivos(NivelRol $nivel, bool $incluirInactivos): void
    {
        if ($incluirInactivos && $nivel->value < NivelRol::Responsable->value) {
            throw new AccesoDenegadoException('Necesitás el rol Responsable para ver los eventos desactivados.');
        }
    }

    /**
     * Para escribir: un evento de todo el grupo pide el rol en el grupo; uno de
     * algunas secciones, el rol en cada una (el Jefe de grupo lo tiene por derivación).
     *
     * @param int[] $idSecciones
     */
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

    // -----------------------------------------------------------------
    // Validaciones
    // -----------------------------------------------------------------

    /**
     * Limpia y controla los datos del evento (modifica el objeto).
     * $anterior es el evento como está en la base, al editar: si la fecha de
     * inicio no cambia, puede quedar en el pasado (un evento que ya empezó se
     * sigue pudiendo corregir).
     */
    private function validar(Evento $evento, ?Evento $anterior): void
    {
        $evento->titulo = Validacion::textoOpcional($evento->titulo, self::LARGO_TITULO, 'El título')
            ?? throw new ReglaNegocioException('El título es obligatorio.');
        $evento->descripcion = Validacion::textoOpcional($evento->descripcion, self::LARGO_DESCRIPCION, 'La descripción')
            ?? throw new ReglaNegocioException('La descripción es obligatoria.');

        $evento->fechaInicio = Validacion::fecha(trim($evento->fechaInicio), 'La fecha de inicio');
        $fin = $evento->fechaFin === null ? '' : trim($evento->fechaFin);
        $evento->fechaFin = $fin === '' ? null : Validacion::fecha($fin, 'La fecha de fin');

        // Las fechas 'AAAA-MM-DD' se pueden comparar como texto.
        $cambiaInicio = $anterior === null || $anterior->fechaInicio !== $evento->fechaInicio;
        if ($cambiaInicio && $evento->fechaInicio < $this->hoy()) {
            throw new ReglaNegocioException('La fecha de inicio no puede ser anterior a hoy.');
        }
        if ($evento->fechaFin !== null && $evento->fechaFin < $evento->fechaInicio) {
            throw new ReglaNegocioException('La fecha de fin no puede ser anterior a la de inicio.');
        }
    }

    /**
     * Saca los repetidos y controla que cada sección exista y sea del grupo.
     *
     * @param array<int|string> $idSecciones Tal como llegan del formulario.
     * @return int[]
     */
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

    // -----------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------

    private function cambiarActivo(int $idUsuario, int $idEvento, bool $activo): void
    {
        $actual = $this->cargarVisible($idUsuario, $idEvento);
        $this->exigirParaEscribir($idUsuario, $actual->evento->idGrupo, $actual->idSecciones, NivelRol::Responsable);

        $actual->evento->activo = $activo;
        $this->eventos->actualizar($actual->evento);
    }

    /**
     * Filtra los eventos que ve ese rol, les agrega sus secciones (una sola
     * consulta para todo el grupo) y, si se piden los próximos, deja solo los
     * que todavía no terminaron.
     *
     * @param Evento[] $eventos Ya ordenados por fecha de inicio (lo hace el repositorio).
     * @return EventoConSecciones[]
     */
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

    /** @param int[] $idSecciones */
    private function guardarSecciones(int $idEvento, array $idSecciones): void
    {
        foreach ($idSecciones as $idSeccion) {
            $this->eventoSecciones->insertar(new EventoSeccion($idEvento, $idSeccion));
        }
    }

    /**
     * @param EventoSeccion[] $filas
     * @return int[]
     */
    private function idsDeSecciones(array $filas): array
    {
        return array_map(fn (EventoSeccion $f) => $f->idSeccion, $filas);
    }

    /** La fecha de hoy en 'AAAA-MM-DD', en la zona horaria de config.php. */
    private function hoy(): string
    {
        return date('Y-m-d');
    }

    private function exigirGrupo(int $idGrupo): void
    {
        if ($this->grupos->buscarPorId($idGrupo) === null) {
            throw new NoEncontradoException('El grupo no existe.');
        }
    }

    private function seccion(int $idSeccion): Seccion
    {
        return $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');
    }
}
