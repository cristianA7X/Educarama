<?php
// src/Controller/Pagina.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Sesion;
use Educarama\Service\Excepcion\SesionRequeridaException;

/**
 * Helper de las páginas de views/pages/ (solo GET: mostrar datos).
 *
 * Cada página empieza así:
 *
 *   require_once __DIR__ . '/../../config/bootstrap.php';
 *   use Educarama\Controller\Pagina;
 *   use Educarama\Controller\UsuarioController;
 *
 *   $datos = Pagina::cargar(fn () => (new UsuarioController())->paginaMiCuenta($_GET));
 *   // ... y el HTML usa $datos['usuario'], etc.
 *
 * Qué hace cargar():
 *  - Si el Controller devuelve una redirección, redirige y corta.
 *  - Si devuelve una página, devuelve sus datos.
 *  - Sin sesión → al login, con ?volver= la página pedida (para volver después de entrar).
 *  - 403, 404 y demás errores → página de error con el código y el mensaje.
 *    Lo que no es de un Service se muestra como "Ocurrió un error inesperado".
 */
final class Pagina
{
    /** Página que se muestra ante un error (recibe $codigo y $mensaje). */
    private const VISTA_ERROR = __DIR__ . '/../../views/includes/error.php';

    private function __construct() {}

    /**
     * @param callable(): Respuesta $controlador
     * @return array<string, mixed> Los datos de la página.
     */
    public static function cargar(callable $controlador): array
    {
        Sesion::iniciar();

        try {
            $respuesta = $controlador();
        } catch (SesionRequeridaException) {
            self::alLogin()->emitir();
        } catch (\Throwable $e) {
            $error = Respuesta::desdeExcepcion($e);
            self::mostrarError($error->codigo, (string) $error->datos['mensaje']);
        }

        if ($respuesta->tipo !== Respuesta::PAGINA) {
            $respuesta->emitir();
        }

        return $respuesta->datos;
    }

    /**
     * Redirección al login que recuerda la página pedida. La ruta se saca de
     * REQUEST_URI sin BASE_URL: '/Educarama/views/pages/x.php?id=3' → 'views/pages/x.php?id=3'.
     */
    public static function alLogin(): Respuesta
    {
        $login  = url('views/pages/login.php');
        $pedida = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $base   = rtrim(BASE_URL, '/') . '/';

        if (str_starts_with($pedida, $base)) {
            $login .= '?volver=' . rawurlencode(substr($pedida, strlen($base)));
        }

        return Respuesta::redireccion($login);
    }

    private static function mostrarError(int $codigo, string $mensaje): never
    {
        http_response_code($codigo);
        require self::VISTA_ERROR;
        exit;
    }
}
