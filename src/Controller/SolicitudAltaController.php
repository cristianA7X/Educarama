<?php
// src/Controller/SolicitudAltaController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\SolicitudAltaService;
use Educarama\Service\UsuarioService;

/**
 * Alta de un grupo nuevo: el usuario envía una solicitud y un admin la revisa
 * (CLAUDE.md, "Reglas de negocio").
 *
 * Páginas (GET, desde views/pages/):
 *   paginaSolicitud  solicitud_alta.php   Cualquier usuario con sesión: el formulario y mis solicitudes
 *   paginaRevision   solicitudes.php      Solo admin: las pendientes
 *
 * Acciones (POST por fetch a public/api.php):
 *   solicitud_alta.enviar    codigo, nombre, seccion_nombre[], seccion_rama[]   → inicio
 *   solicitud_alta.aprobar   id_solicitud                                       → revisión (admin)
 *   solicitud_alta.rechazar  id_solicitud, motivo                               → revisión (admin)
 */
final class SolicitudAltaController extends Controlador
{
    private SolicitudAltaService $solicitudes;

    public function __construct(?UsuarioService $usuarioService = null, ?SolicitudAltaService $solicitudes = null)
    {
        parent::__construct($usuarioService);
        $this->solicitudes = $solicitudes ?? new SolicitudAltaService();
    }

    // -----------------------------------------------------------------
    // Páginas
    // -----------------------------------------------------------------

    /**
     * Datos: 'solicitudes' (SolicitudEnPantalla[], las mías de la más nueva a
     * la más vieja, con el motivo si se rechazaron), 'ramas' (Rama[], para el
     * select de cada sección) y 'hayPendiente': con una pendiente no se puede
     * enviar otra, así que la vista oculta el formulario.
     */
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

    /**
     * Datos: 'pendientes' (SolicitudEnPantalla[], de la más vieja a la más
     * nueva, cada una con quién la envió). Solo admin: cualquier otro, 403.
     */
    public function paginaRevision(): Respuesta
    {
        return Respuesta::pagina([
            'pendientes' => $this->solicitudes->pendientesEnPantalla($this->usuario()->id),
        ]);
    }

    // -----------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
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

    /** @param array<string, mixed> $post */
    public function aprobar(array $post): Respuesta
    {
        $idAdmin = $this->usuario()->id;
        $idSolicitud = self::entero($post, 'id_solicitud');

        // Se lee antes: después de aprobar ya no está pendiente, y de acá salen el nombre y el grupo para el mensaje.
        $antes = $this->solicitudes->detalle($idAdmin, $idSolicitud);
        $this->solicitudes->aprobar($idAdmin, $idSolicitud);

        $grupo = $antes->solicitud->datosGrupo;
        Flash::guardar(Flash::EXITO, "Grupo {$grupo['codigo']} «{$grupo['nombre']}» creado. {$antes->nombreSolicitante} quedó como Jefe del grupo.");
        return Respuesta::exito(redireccion: url('views/pages/solicitudes.php'));
    }

    /** @param array<string, mixed> $post */
    public function rechazar(array $post): Respuesta
    {
        $this->solicitudes->rechazar($this->usuario()->id, self::entero($post, 'id_solicitud'), self::texto($post, 'motivo'));

        Flash::guardar(Flash::EXITO, 'Solicitud rechazada. El solicitante va a ver el motivo.');
        return Respuesta::exito(redireccion: url('views/pages/solicitudes.php'));
    }

    // -----------------------------------------------------------------

    /**
     * Las filas de secciones del formulario: dos listas paralelas, el nombre y
     * la rama de cada una (seccion_nombre[] y seccion_rama[]). Las filas que
     * quedaron completamente vacías se descartan; una a medias (nombre sin
     * rama, o al revés) la rechaza el Service con su mensaje.
     *
     * @return list<array{nombre: string, id_rama: int}>
     * @throws ReglaNegocioException si las listas no vienen juntas
     */
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
