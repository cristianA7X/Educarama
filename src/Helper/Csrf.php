<?php
// src/Helper/Csrf.php

declare(strict_types=1);

namespace Educarama\Helper;

/**
 * Protección contra CSRF (Cross-Site Request Forgery): que otra página, abierta
 * en el mismo navegador, mande un formulario a Educarama en nombre del usuario
 * logueado (por ejemplo, para borrar un documento).
 *
 * Cómo funciona: cada sesión tiene un token secreto y aleatorio. Todo
 * formulario POST lo manda en un campo oculto, y el Controller lo compara con
 * el de la sesión antes de hacer nada. Otra página no puede leer el token, así
 * que no puede armar un formulario válido.
 *
 * En la vista, dentro del <form method="post">:
 *
 *   <?= Csrf::campo() ?>
 *
 * En el Controller, antes de procesar el POST:
 *
 *   if (!Csrf::esValido($_POST[Csrf::CAMPO] ?? null)) {
 *       http_response_code(403);
 *       exit('La sesión expiró. Volvé a cargar la página e intentá de nuevo.');
 *   }
 *
 * Regla del proyecto (CLAUDE.md): todo formulario POST lo usa.
 */
final class Csrf
{
    /** Nombre del campo oculto del formulario. */
    public const CAMPO = 'csrf_token';

    /** Clave donde se guarda el token en $_SESSION. */
    private const CLAVE_SESION = '_csrf_token';

    private function __construct() {}

    /** El token de la sesión; lo crea la primera vez. */
    public static function token(): string
    {
        self::iniciarSesion();

        if (!isset($_SESSION[self::CLAVE_SESION]) || !is_string($_SESSION[self::CLAVE_SESION])) {
            $_SESSION[self::CLAVE_SESION] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLAVE_SESION];
    }

    /** El campo oculto listo para imprimir dentro de un <form method="post">. */
    public static function campo(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::CAMPO,
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8'),
        );
    }

    /**
     * Compara el token recibido con el de la sesión. hash_equals tarda lo mismo
     * acierte o no, así no se puede adivinar el token midiendo tiempos.
     */
    public static function esValido(mixed $tokenRecibido): bool
    {
        self::iniciarSesion();
        $tokenSesion = $_SESSION[self::CLAVE_SESION] ?? null;

        return is_string($tokenRecibido)
            && is_string($tokenSesion)
            && hash_equals($tokenSesion, $tokenRecibido);
    }

    /**
     * Crea un token nuevo. Llamarlo al iniciar y al cerrar sesión, así un token
     * viejo no sirve para la sesión nueva.
     */
    public static function regenerar(): void
    {
        self::iniciarSesion();
        $_SESSION[self::CLAVE_SESION] = bin2hex(random_bytes(32));
    }

    private static function iniciarSesion(): void
    {
        Sesion::iniciar();
    }
}
