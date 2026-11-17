<?php
// src/Controller/Api.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Almacen\AlmacenArchivos;
use Educarama\Almacen\ArchivoInvalidoException;
use Educarama\Contenedor;
use Educarama\Helper\Csrf;
use Educarama\Helper\JwtHelper;

final class Api
{
    public function __construct(
        private readonly array $acciones,
        private readonly Contenedor $contenedor,
        private readonly AlmacenArchivos $almacen
    ) {}

    public function atender(string $metodoHttp, array $get, array $post = [], array $files = []): Respuesta
    {
        // 1. Manejo de CORS para la APP Mobile (React Native / Fetch cruzado)
        if ($metodoHttp === 'OPTIONS') {
            return Respuesta::exito(); // Salida rápida para preflights
        }

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

            if ($metodoEsperado === 'POST' && $post === [] && $files === []) {
                try {
                    $this->almacen->verificarLimiteDelServidor();
                } catch (ArchivoInvalidoException $e) {
                    return Respuesta::error($e->getMessage(), 413);
                }
            }

            // 2. Control de CSRF (Solo si NO usa JWT)
            $tokenJwt = JwtHelper::obtenerCabecera();
            $esAppMobile = JwtHelper::extraerId($tokenJwt) !== null;

            if ($metodoEsperado === 'POST' && !$esAppMobile && !Csrf::esValido($post[Csrf::CAMPO] ?? null)) {
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

    public function ejecutar(): never
    {
        // Emitir cabeceras CORS en el Entry Point
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        $this->atender($_SERVER['REQUEST_METHOD'] ?? 'GET', $_GET, $_POST, $_FILES)->emitir();
    }
}