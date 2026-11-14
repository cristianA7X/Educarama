<?php
// src/Controller/MenuController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

/**
 * Los datos del menú lateral (el drawer), que muestra casi toda página.
 *
 * No es una página: lo llama views/includes/cabecera.php, igual que cualquier
 * página llama a su Controller:
 *
 *   $datos = Pagina::cargar(fn () => (new MenuController())->datosMenu());
 *
 * El menú es contextual (CLAUDE.md, "Views"): un usuario puede entrar a varios
 * grupos y secciones, y cada una trae sus propios links.
 */
final class MenuController extends Controlador
{
    private SeccionService $secciones;

    public function __construct(?UsuarioService $usuarioService = null, ?SeccionService $secciones = null)
    {
        parent::__construct($usuarioService);
        $this->secciones = $secciones ?? new SeccionService();
    }

    /**
     * Datos: 'usuario' (UsuarioSesion: nombre, y si es admin para el link a
     * las solicitudes) y 'grupos' (GrupoEnPantalla[]: cada grupo con las
     * secciones que ve; solo las que tienen 'nivel' tienen links propios).
     */
    public function datosMenu(): Respuesta
    {
        $usuario = $this->usuario();

        return Respuesta::pagina([
            'usuario' => $usuario,
            'grupos'  => $this->secciones->misGrupos($usuario->id),
        ]);
    }
}
