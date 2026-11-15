<?php
// src/Controller/Api.php
declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Almacen\AlmacenArchivos;
use Educarama\Almacen\ArchivoInvalidoException;
use Educarama\Helper\Csrf;
use Educarama\Contenedor;

final class Api
{
    public function __construct(
        private readonly array $acciones,
        private readonly Contenedor $contenedor,
        private readonly AlmacenArchivos $almacen
    ) {}

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

    public function ejecutar(): never
    {
        $this->atender($_SERVER['REQUEST_METHOD'] ?? 'GET', $_GET, $_POST, $_FILES)->emitir();
    }
}