<?php
// src/Controller/SeccionController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Service\EventoService;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

/**
 * Pantallas de sección y de grupo. Son solo de lectura: las secciones no se
 * crean ni se editan desde la aplicación (nacen al aprobar una solicitud de alta).
 *
 * Páginas (GET, desde views/pages/):
 *   paginaPerfil  perfil_seccion.php?id=   Cualquier acceso a la sección (Supervisor incluido)
 *   paginaGrupo   grupo.php?id=            Rol de grupo, o acceso a alguna de sus secciones
 *
 * Reemplaza a perfil_rama.php y ramas.php del legacy (en el legacy, "rama"
 * era la instancia: hoy es la sección).
 */
final class SeccionController extends Controlador
{
    private SeccionService $secciones;
    private EventoService $eventos;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?SeccionService $secciones = null,
        ?EventoService $eventos = null,
    ) {
        parent::__construct($usuarioService);
        $this->secciones = $secciones ?? new SeccionService();
        $this->eventos = $eventos ?? new EventoService();
    }

    /**
     * Perfil de la sección. Datos: 'seccion' (SeccionEnPantalla, con los
     * permisos para mostrar los links a protagonistas, patrullas y eventos),
     * 'conteos' (ConteoSeccion: sin datos personales, así que lo ve también
     * el Supervisor) y 'proximos' (EventoConSecciones[]).
     *
     * @param array<string, mixed> $get id
     */
    public function paginaPerfil(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::entero($get, 'id');

        $seccion = $this->secciones->encabezado($idUsuario, $idSeccion);  // 404 si no tiene acceso

        return Respuesta::pagina([
            'seccion'  => $seccion,
            'conteos'  => $this->secciones->conteos($idUsuario, $idSeccion),
            'proximos' => $this->eventos->proximosPorSeccion($idUsuario, $idSeccion),
        ]);
    }

    /**
     * Pantalla del grupo. Datos: 'grupo' (GrupoEnPantalla: el grupo, el rol de
     * grupo del usuario y las secciones que ve) y 'proximos' (los eventos que
     * ve del grupo; si entra solo por una sección, los de esa sección).
     *
     * @param array<string, mixed> $get id
     */
    public function paginaGrupo(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idGrupo = self::entero($get, 'id');

        return Respuesta::pagina([
            'grupo'    => $this->secciones->grupo($idUsuario, $idGrupo),  // 404 si no ve nada del grupo
            'proximos' => $this->eventos->proximosPorGrupo($idUsuario, $idGrupo),
        ]);
    }
}
