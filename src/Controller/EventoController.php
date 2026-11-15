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

final class EventoController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly EventoService $eventos,
        private readonly SeccionService $secciones
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaGeneral(): Respuesta
    {
        return Respuesta::pagina([
            'generales' => $this->eventos->eventosGenerales($this->usuario()->id),
        ]);
    }

    public function paginaListado(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $inactivos = self::booleano($get, 'inactivos');
        $idSeccion = self::enteroOpcional($get, 'id_seccion');
        
        if ($idSeccion !== null) {
            return Respuesta::pagina([
                'modo'     => 'seccion',
                'seccion'  => $this->secciones->encabezado($idUsuario, $idSeccion),
                'pantalla' => $this->eventos->pantallaSeccion($idUsuario, $idSeccion, $inactivos),
            ]);
        }
        
        $idGrupo = self::entero($get, 'id_grupo');
        return Respuesta::pagina([
            'modo'     => 'grupo',
            'grupo'    => $this->secciones->grupo($idUsuario, $idGrupo),
            'pantalla' => $this->eventos->pantallaGrupo($idUsuario, $idGrupo, $inactivos),
        ]);
    }

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
            activo: true,
            esPublico: self::booleano($post, 'es_publico'),
            idTipoEvento: null,
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

    public function desactivar(array $post): Respuesta
    {
        return $this->cambiar($post, 'desactivar', 'Evento desactivado: ya no aparece en los listados.');
    }

    public function reactivar(array $post): Respuesta
    {
        return $this->cambiar($post, 'reactivar', 'Evento reactivado.');
    }

    public function eliminar(array $post): Respuesta
    {
        return $this->cambiar($post, 'eliminar', 'Evento eliminado.');
    }

    private function cambiar(array $post, string $operacion, string $mensaje): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idEvento = self::entero($post, 'id_evento');
        $idGrupo = $this->eventos->buscar($idUsuario, $idEvento)->evento->idGrupo;
        $this->eventos->$operacion($idUsuario, $idEvento);
        Flash::guardar(Flash::EXITO, $mensaje);
        return Respuesta::exito(redireccion: self::volver($post, $idGrupo));
    }

    private static function secciones(array $post): array
    {
        return match (self::texto($post, 'alcance')) {
            'grupo'     => [],
            'secciones' => self::listaDeEnteros($post, 'secciones')
                ?: throw new ReglaNegocioException('Elegí al menos una sección, o marcá "Todo el grupo".'),
            default     => throw new ReglaNegocioException('Elegí si el evento es de todo el grupo o de algunas secciones.'),
        };
    }

    private static function prioridad(array $post): Prioridad
    {
        $texto = self::texto($post, 'prioridad');
        if ($texto === '') {
            return Prioridad::Media;
        }
        return Prioridad::tryFrom($texto)
            ?? throw new ReglaNegocioException('La prioridad elegida no es válida.');
    }

    private static function volver(array $post, int $idGrupo): string
    {
        return url(self::rutaInterna(self::texto($post, 'volver')) ?? 'views/pages/eventos.php?id_grupo=' . $idGrupo);
    }
}