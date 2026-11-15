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
 *   $app = require_once __DIR__ . '/../../src/main.php';
 *   use Educarama\Controller\Pagina;
 *   use Educarama\Controller\UsuarioController;
 *
 *   $datos = Pagina::cargar(fn () => $app->obtener(UsuarioController::class)->paginaMiCuenta($_GET));
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

            // Redirecciones y archivos se emiten acá adentro: si enviar un
            // archivo falla (no está en el disco), cae en el mismo catch.
            if ($respuesta->tipo !== Respuesta::PAGINA) {
                $respuesta->emitir();
            }

            return $respuesta->datos;
        } catch (SesionRequeridaException) {
            self::alLogin()->emitir();
        } catch (\Throwable $e) {
            // ── TRUCO DE DEPUGRACIÓN TEMPORAL ──
            // Esto imprime el error real en pantalla para saber qué falló.
            echo '<div style="background:#791fbf; color:white; padding:20px; margin:20px; border-radius:10px; z-index:999999; position:relative; font-family:monospace; box-shadow:0 5px 20px rgba(0,0,0,0.5);">';
            echo '<h3 style="color:#fdd617; margin-top:0;">⚠️ ERROR DETECTADO EN EL CONTROLADOR:</h3>';
            echo '<p><b>Mensaje:</b> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><b>Archivo:</b> ' . htmlspecialchars($e->getFile()) . ' en la línea <b>' . $e->getLine() . '</b></p>';
            echo '<details><summary style="cursor:pointer; font-weight:bold; margin-bottom:10px;">Ver pila de llamadas (Trace)</summary>';
            echo '<pre style="background:rgba(0,0,0,0.3); padding:10px; overflow:auto; max-height:200px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</details>';
            echo '</div>';

            $error = Respuesta::desdeExcepcion($e);
            if (headers_sent()) {
                // Ya salió parte de la respuesta (ej. un archivo a medias): no se puede mostrar otra página.
                exit;
            }
            self::mostrarError($error->codigo, (string) $error->datos['mensaje']);
        }
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