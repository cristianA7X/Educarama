<?php
// src/Controller/EventoController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Model\Enum\Prioridad;
use Educarama\Model\Evento;
use Educarama\Service\EventoService;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

/**
 * Eventos de un grupo: de todo el grupo o de algunas de sus secciones.
 *
 * Páginas (GET, desde views/pages/):
 *   paginaListado     eventos.php?id_grupo=     Pantalla del grupo (rol de grupo)
 *                     eventos.php?id_seccion=   Pantalla de la sección (acceso a la sección)
 *                     &inactivos=1              También los desactivados (Responsable)
 *   paginaFormulario  evento_form.php?id_grupo= (alta)   Responsable en el grupo o en alguna sección
 *                     evento_form.php?id=       (edición) poder editar el evento como está
 *
 * Acciones (POST por fetch a public/api.php). Todas vuelven al listado de donde
 * se vino (campo "volver", validado) con un Flash:
 *   evento.guardar     id (vacío = alta), id_grupo (solo alta), titulo, descripcion,
 *                      fecha_inicio, fecha_fin, prioridad, es_publico,
 *                      alcance ('grupo' | 'secciones'), secciones[]
 *   evento.desactivar  id_evento
 *   evento.reactivar   id_evento
 *   evento.eliminar    id_evento (Jefe)
 */
final class EventoController extends Controlador
{
    private EventoService $eventos;
    private SeccionService $secciones;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?EventoService $eventos = null,
        ?SeccionService $secciones = null,
    ) {
        parent::__construct($usuarioService);
        $this->eventos = $eventos ?? new EventoService();
        $this->secciones = $secciones ?? new SeccionService();
    }

    // -----------------------------------------------------------------
    // Páginas
    // -----------------------------------------------------------------

    /**
     * Vista general de eventos: todos los eventos de todos los grupos a los que
     * el usuario tiene acceso. Sin parámetros (drawer → Eventos).
     *
     * Datos: 'generales' (EventosGenerales).
     */
    public function paginaGeneral(): Respuesta
    {
        return Respuesta::pagina([
            'generales' => $this->eventos->eventosGenerales($this->usuario()->id),
        ]);
    }

    /**
     * Datos: 'modo' ('grupo' | 'seccion'), 'grupo' (GrupoEnPantalla, en modo
     * grupo) o 'seccion' (SeccionEnPantalla, en modo sección), y 'pantalla'
     * (EventosEnPantalla).
     *
     * @param array<string, mixed> $get id_grupo o id_seccion, inactivos (opcional)
     */
    public function paginaListado(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $inactivos = self::booleano($get, 'inactivos');
        $idSeccion = self::enteroOpcional($get, 'id_seccion');

        if ($idSeccion !== null) {
            return Respuesta::pagina([
                'modo'     => 'seccion',
                'seccion'  => $this->secciones->encabezado($idUsuario, $idSeccion),  // 404 si no tiene acceso
                'pantalla' => $this->eventos->pantallaSeccion($idUsuario, $idSeccion, $inactivos),
            ]);
        }

        $idGrupo = self::entero($get, 'id_grupo');
        return Respuesta::pagina([
            'modo'     => 'grupo',
            'grupo'    => $this->secciones->grupo($idUsuario, $idGrupo),  // 404 si no ve nada del grupo
            'pantalla' => $this->eventos->pantallaGrupo($idUsuario, $idGrupo, $inactivos),
        ]);
    }

    /**
     * Datos: 'opciones' (OpcionesEvento: grupo, secciones elegibles y si puede
     * "todo el grupo") y 'evento' (EventoConSecciones, null en un alta).
     *
     * @param array<string, mixed> $get id_grupo (alta) o id (edición)
     */
    public function paginaFormulario(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idEvento = self::enteroOpcional($get, 'id');

        $evento = $idEvento === null ? null : $this->eventos->paraEditar($idUsuario, $idEvento);
        $idGrupo = $evento?->evento->idGrupo ?? self::entero($get, 'id_grupo');

        return Respuesta::pagina([
            'opciones' => $this->eventos->opcionesFormulario($idUsuario, $idGrupo),
            'evento'   => $evento,
        ]);
    }

    // -----------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------

    /**
     * Alta si no viene id; edición si viene. En la edición el grupo sale de la
     * base (lo fuerza el Service), no del formulario.
     *
     * @param array<string, mixed> $post
     */
    public function guardar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $id = self::enteroOpcional($post, 'id');
        $idGrupo = $id === null ? self::entero($post, 'id_grupo') : $this->eventos->buscar($idUsuario, $id)->evento->idGrupo;

        $evento = new Evento(
            id: $id,
            idGrupo: $idGrupo,
            titulo: self::texto($post, 'titulo'),
            descripcion: self::texto($post, 'descripcion'),
            fechaInicio: self::texto($post, 'fecha_inicio'),
            fechaFin: self::fechaOpcional($post, 'fecha_fin'),
            prioridad: self::prioridad($post),
            activo: true,         // Lo decide el Service (alta: activo; edición: el de la base)
            esPublico: self::booleano($post, 'es_publico'),
            idTipoEvento: null,   // Reservado
        );
        $idSecciones = self::secciones($post);

        if ($id === null) {
            $this->eventos->crear($idUsuario, $evento, $idSecciones);
            Flash::guardar(Flash::EXITO, 'Evento creado.');
        } else {
            $this->eventos->actualizar($idUsuario, $evento, $idSecciones);
            Flash::guardar(Flash::EXITO, 'Cambios guardados.');
        }

        return Respuesta::exito(redireccion: self::volver($post, $idGrupo));
    }

    /** @param array<string, mixed> $post */
    public function desactivar(array $post): Respuesta
    {
        return $this->cambiar($post, 'desactivar', 'Evento desactivado: ya no aparece en los listados.');
    }

    /** @param array<string, mixed> $post */
    public function reactivar(array $post): Respuesta
    {
        return $this->cambiar($post, 'reactivar', 'Evento reactivado.');
    }

    /** @param array<string, mixed> $post */
    public function eliminar(array $post): Respuesta
    {
        return $this->cambiar($post, 'eliminar', 'Evento eliminado.');
    }

    // -----------------------------------------------------------------

    /**
     * Desactivar, reactivar o eliminar: lee el evento (para saber su grupo y
     * volver a su listado), hace la operación y deja el Flash.
     *
     * @param array<string, mixed> $post
     */
    private function cambiar(array $post, string $operacion, string $mensaje): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idEvento = self::entero($post, 'id_evento');
        $idGrupo = $this->eventos->buscar($idUsuario, $idEvento)->evento->idGrupo;

        $this->eventos->$operacion($idUsuario, $idEvento);

        Flash::guardar(Flash::EXITO, $mensaje);
        return Respuesta::exito(redireccion: self::volver($post, $idGrupo));
    }

    /**
     * "Todo el grupo" (lista vacía) o "algunas secciones" (al menos una).
     * Se pide el alcance explícito: si no, destildar todas las casillas
     * convertiría el evento en uno de todo el grupo sin querer.
     *
     * @return int[]
     * @throws ReglaNegocioException
     */
    private static function secciones(array $post): array
    {
        return match (self::texto($post, 'alcance')) {
            'grupo'     => [],
            'secciones' => self::listaDeEnteros($post, 'secciones')
                ?: throw new ReglaNegocioException('Elegí al menos una sección, o marcá "Todo el grupo".'),
            default     => throw new ReglaNegocioException('Elegí si el evento es de todo el grupo o de algunas secciones.'),
        };
    }

    /** Alta, media o baja; si no viene, media (el valor por defecto de la base). */
    private static function prioridad(array $post): Prioridad
    {
        $texto = self::texto($post, 'prioridad');
        if ($texto === '') {
            return Prioridad::Media;
        }
        return Prioridad::tryFrom($texto)
            ?? throw new ReglaNegocioException('La prioridad elegida no es válida.');
    }

    /**
     * A qué listado volver: el que mandó la página (eventos.php del grupo o de
     * una sección), si es una ruta del proyecto; si no, el del grupo.
     */
    private static function volver(array $post, int $idGrupo): string
    {
        return url(self::rutaInterna(self::texto($post, 'volver')) ?? 'views/pages/eventos.php?id_grupo=' . $idGrupo);
    }
}
