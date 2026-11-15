<?php
// src/Helper/Flash.php

declare(strict_types=1);

namespace Educarama\Helper;

/**
 * Mensajes de una sola vez: se guardan antes de un redirect y se muestran en
 * la página siguiente ("Protagonista guardado."). Al leerlos se borran.
 *
 * En el Controller, antes de redirigir:
 *
 *   Flash::guardar(Flash::EXITO, 'Cuenta creada.');
 *
 * En la vista (lo hace el include del layout):
 *
 *   foreach (Flash::tomar() as $m) { ... $m['tipo'] ... $m['mensaje'] ... }
 *
 * Es un Helper, como Csrf: la regla "UsuarioService es el único Service que
 * toca $_SESSION" sigue valiendo, porque esto no es un Service.
 */
final class Flash
{
    public const EXITO = 'exito';
    public const ERROR = 'error';
    public const AVISO = 'aviso';

    private const CLAVE_SESION = '_flash';

    private function __construct() {}

    public static function guardar(string $tipo, string $mensaje): void
    {
        Sesion::iniciar();
        $_SESSION[self::CLAVE_SESION][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /**
     * Devuelve los mensajes guardados y los borra.
     *
     * @return list<array{tipo: string, mensaje: string}>
     */
    public static function tomar(): array
    {
        Sesion::iniciar();
        $mensajes = $_SESSION[self::CLAVE_SESION] ?? [];
        unset($_SESSION[self::CLAVE_SESION]);

        return is_array($mensajes) ? array_values($mensajes) : [];
    }
}
