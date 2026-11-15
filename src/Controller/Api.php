<?php
// src/Controller/Api.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Almacen\AlmacenArchivos;
use Educarama\Almacen\ArchivoInvalidoException;
use Educarama\Contenedor;
use Educarama\Helper\Csrf;

/**
 * Atiende los pedidos por fetch de public/api.php?accion=...
 *
 * public/api.php tiene la lista fija de acciones (ACCIONES): solo se puede
 * llamar a lo que está ahí, nunca a un método armado con lo que manda el
 * navegador. Cada acción dice su método HTTP y a qué Controller va:
 *
 *   'usuario.iniciarSesion' => ['POST', UsuarioController::class, 'iniciarSesion'],
 *
 * Qué hace con cada pedido:
 *  1. Busca la acción; si no existe, 404.
 *  2. Controla el método HTTP; si no coincide, 405.
 *  3. Si es POST y llegó vacío porque superó post_max_size, 413 con el mensaje de tamaño máximo.
 *  4. Si es POST, controla el token CSRF (campo csrf_token del FormData); si no sirve, 403 (Apache cambia los códigos que no conoce, como 419, por 500).
 *  5. Le pide el Controller al contenedor y lo llama: los GET reciben ($get), los POST reciben ($post, $files).
 *  6. Traduce cualquier excepción con Respuesta::desdeExcepcion().
 *
 * Todas las respuestas son JSON (ver el formato en Respuesta). La sesión la
 * arrancan Csrf y UsuarioService cuando la necesitan (Sesion::iniciar()).
 */
final class Api
{
    /**
     * @param array<string, array{0: string, 1: class-string<Controlador>, 2: string}> $acciones La lista de public/api.php.
     * @param Contenedor      $contenedor De donde salen los Controllers, ya armados (src/main.php).
     * @param AlmacenArchivos $almacen    Para detectar un pedido más grande que post_max_size.
     */
    public function __construct(
        private readonly array $acciones,
        private readonly Contenedor $contenedor,
        private readonly AlmacenArchivos $almacen
    ) {}

    /**
     * Arma la respuesta sin emitirla (así se puede probar por CLI).
     *
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public function atender(string $metodoHttp, array $get, array $post = [], array $files = []): Respuesta
    {
        try {
            $nombre = is_string($get['accion'] ?? null) ? $get['accion'] : '';
            $accion = $this->acciones[$nombre] ?? null;
            if ($accion === null) {
                return Respuesta::error('La acción pedida no existe.', 404);
            }

            [$metodoEsperado, $clase, $metodo] = $accion;
            if (strtoupper($metodoHttp) !== $metodoEsperado) {
                return Respuesta::error('Método no permitido.', 405);
            }

            // Si el pedido superó post_max_size, PHP lo descarta entero y llega
            // vacío: sin esto, se vería como CSRF faltante ("La sesión expiró").
            if ($metodoEsperado === 'POST' && $post === [] && $files === []) {
                try {
                    $this->almacen->verificarLimiteDelServidor();
                } catch (ArchivoInvalidoException $e) {
                    return Respuesta::error($e->getMessage(), 413);
                }
            }

            if ($metodoEsperado === 'POST' && !Csrf::esValido($post[Csrf::CAMPO] ?? null)) {
                return Respuesta::error('La sesión expiró. Volvé a cargar la página e intentá de nuevo.', 403);
            }

            $controlador = $this->contenedor->obtener($clase);
            return $metodoEsperado === 'POST'
                ? $controlador->$metodo($post, $files)
                : $controlador->$metodo($get);
        } catch (\Throwable $e) {
            return Respuesta::desdeExcepcion($e);
        }
    }

    /** Lo que llama public/api.php: atiende el pedido real y emite la respuesta. */
    public function ejecutar(): never
    {
        $this->atender($_SERVER['REQUEST_METHOD'] ?? 'GET', $_GET, $_POST, $_FILES)->emitir();
    }
}
