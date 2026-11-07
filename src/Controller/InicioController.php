<?php
// src/Controller/InicioController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Service\EventoService;
use Educarama\Service\SeccionService;
use Educarama\Service\SolicitudAltaService;
use Educarama\Service\UsuarioService;

/**
 * La página de inicio, adonde se llega después del login.
 *
 * Página (GET):
 *   paginaInicio  inicio.php
 *
 * Con accesos: sus grupos, con las secciones a las que entra y los próximos
 * eventos de cada uno. Sin ningún acceso (recién registrado): el estado de su
 * solicitud de alta, para que sepa qué sigue.
 */
final class InicioController extends Controlador
{
    private SeccionService $secciones;
    private EventoService $eventos;
    private SolicitudAltaService $solicitudes;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?SeccionService $secciones = null,
        ?EventoService $eventos = null,
        ?SolicitudAltaService $solicitudes = null,
    ) {
        parent::__construct($usuarioService);
        $this->secciones = $secciones ?? new SeccionService();
        $this->eventos = $eventos ?? new EventoService();
        $this->solicitudes = $solicitudes ?? new SolicitudAltaService();
    }

    /**
     * Datos:
     *  - 'usuario' (UsuarioSesion: nombre y si es admin, para el link a las solicitudes pendientes);
     *  - 'grupos' (list de ['grupo' => GrupoEnPantalla, 'proximos' => EventoConSecciones[]]);
     *  - 'solicitud' (SolicitudAlta|null): la más reciente, solo si no tiene ningún grupo.
     *    null también si nunca envió una: la vista ofrece el link para pedir el alta.
     */
    public function paginaInicio(): Respuesta
    {
        $usuario = $this->usuario();

        $grupos = array_map(fn ($g) => [
            'grupo'    => $g,
            'proximos' => $this->eventos->proximosPorGrupo($usuario->id, (int) $g->grupo->id),
        ], $this->secciones->misGrupos($usuario->id));

        return Respuesta::pagina([
            'usuario'   => $usuario,
            'grupos'    => $grupos,
            'solicitud' => $grupos === [] ? ($this->solicitudes->misSolicitudes($usuario->id)[0] ?? null) : null,
        ]);
    }
}
