<?php
// src/Controller/SolicitudAltaController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\SolicitudAltaService;
use Educarama\Service\UsuarioService;

final class SolicitudAltaController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly SolicitudAltaService $solicitudes
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaSolicitud(): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $solicitudes = $this->solicitudes->misSolicitudesEnPantalla($idUsuario);

        return Respuesta::pagina([
            'solicitudes'  => $solicitudes,
            'ramas'        => $this->solicitudes->ramas(),
            'hayPendiente' => array_filter($solicitudes, fn ($s) => $s->solicitud->estado === EstadoSolicitud::Pendiente) !== [],
        ]);
    }

    public function paginaRevision(): Respuesta
    {
        return Respuesta::pagina([
            'pendientes' => $this->solicitudes->pendientesEnPantalla($this->usuario()->id),
        ]);
    }

    public function enviar(array $post): Respuesta
    {
        $this->solicitudes->enviar(
            $this->usuario()->id,
            ['codigo' => self::texto($post, 'codigo'), 'nombre' => self::texto($post, 'nombre')],
            self::secciones($post),
        );

        Flash::guardar(Flash::EXITO, 'Solicitud enviada. Un administrador la va a revisar.');
        return Respuesta::exito(redireccion: url('views/pages/inicio.php'));
    }

    public function aprobar(array $post): Respuesta
    {
        $idAdmin = $this->usuario()->id;
        $idSolicitud = self::entero($post, 'id_solicitud');

        $antes = $this->solicitudes->detalle($idAdmin, $idSolicitud);
        $this->solicitudes->aprobar($idAdmin, $idSolicitud);

        $grupo = $antes->solicitud->datosGrupo;
        Flash::guardar(Flash::EXITO, "Grupo {$grupo['codigo']} «{$grupo['nombre']}» creado. {$antes->nombreSolicitante} quedó como Jefe del grupo.");
        return Respuesta::exito(redireccion: url('views/pages/solicitudes.php'));
    }

    public function rechazar(array $post): Respuesta
    {
        $this->solicitudes->rechazar($this->usuario()->id, self::entero($post, 'id_solicitud'), self::texto($post, 'motivo'));

        Flash::guardar(Flash::EXITO, 'Solicitud rechazada. El solicitante va a ver el motivo.');
        return Respuesta::exito(redireccion: url('views/pages/solicitudes.php'));
    }

    private static function secciones(array $post): array
    {
        $nombres = $post['seccion_nombre'] ?? [];
        $ramas = $post['seccion_rama'] ?? [];

        if (!is_array($nombres) || !is_array($ramas) || count($nombres) !== count($ramas)) {
            throw new ReglaNegocioException('Los datos de las secciones no son válidos. Volvé a cargar la página.');
        }

        $secciones = [];
        foreach (array_values($nombres) as $i => $nombre) {
            $nombre = trim(self::texto(['v' => $nombre], 'v'));
            $rama = trim(self::texto(['v' => array_values($ramas)[$i]], 'v'));

            if ($nombre === '' && $rama === '') {
                continue;
            }
            $secciones[] = ['nombre' => $nombre, 'id_rama' => ctype_digit($rama) ? (int) $rama : 0];
        }

        return $secciones;
    }
}