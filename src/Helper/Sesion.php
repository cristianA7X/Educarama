<?php
// src/Helper/Sesion.php

declare(strict_types=1);

namespace Educarama\Helper;

/**
 * Arranca la sesión de PHP siempre con la misma configuración segura.
 * Todo el proyecto la inicia con Sesion::iniciar(), nunca con session_start() suelto.
 *
 *  - Cookie httponly: el JavaScript de la página no la puede leer.
 *  - SameSite=Lax: el navegador no la manda en formularios que vienen de otro sitio.
 *  - secure si la página se sirve por HTTPS.
 *  - use_strict_mode: PHP no acepta un id de sesión inventado por el cliente
 *    (evita que alguien "fije" la sesión de otro).
 *
 * Qué se guarda en la sesión (el id del usuario logueado) lo decide UsuarioService.
 */
final class Sesion
{
    private function __construct() {}

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_set_cookie_params([
            'lifetime' => 0,  // Hasta que se cierra el navegador
            'path'     => '/',
            'secure'   => ($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
