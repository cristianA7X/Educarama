<?php
// src/Controller/Respuesta.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Almacen\AlmacenArchivos;
use Educarama\Almacen\ArchivoInvalidoException;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\RequisitosPendientesException;
use Educarama\Service\Excepcion\ServicioException;
use Educarama\Service\Excepcion\SesionRequeridaException;
use Educarama\Service\Vista\Aviso;

/**
 * Lo que devuelve cada método de un Controller. El Controller NO manda headers
 * ni hace echo: arma una Respuesta y el punto de entrada (Api o Pagina) la
 * emite. Así los Controllers se pueden probar por CLI mirando lo que devuelven.
 *
 * Tipos:
 *  - pagina:      datos para la vista (la página los usa para armar el HTML).
 *  - json:        respuesta de public/api.php (fetch).
 *  - redireccion: ir a otra página del proyecto.
 *  - archivo:     descargar o mostrar un archivo del almacén.
 *
 * Formato del JSON (lo lee el JS de las vistas):
 *
 *   éxito:      {"ok": true,  "datos": {...}, "mensaje": "...", "redireccion": "/Educarama/..."}
 *   error:      {"ok": false, "mensaje": "..."}
 *   confirmar:  {"ok": false, "confirmar": true, "mensaje": "...", "avisos": [{"tipo": "...", "mensaje": "..."}]}
 *
 * "mensaje" y "redireccion" son opcionales. Si viene "redireccion", el JS va a
 * esa página; el mensaje para mostrar allá viaja en el Flash, no en el JSON.
 */
final class Respuesta
{
    public const PAGINA      = 'pagina';
    public const JSON        = 'json';
    public const REDIRECCION = 'redireccion';
    public const ARCHIVO     = 'archivo';

    /** Lo único que ve el usuario de un error que no es de un Service. */
    public const MENSAJE_INESPERADO = 'Ocurrió un error inesperado. Probá de nuevo en un rato.';

    /**
     * @param array<string, mixed> $datos   Datos de la página, o el cuerpo del JSON.
     * @param array<string, mixed> $archivo Solo para ARCHIVO: ruta, nombre y enLinea.
     */
    private function __construct(
        public readonly string  $tipo,
        public readonly array   $datos = [],
        public readonly int     $codigo = 200,
        public readonly ?string $url = null,
        public readonly array   $archivo = [],
    ) {}

    // -----------------------------------------------------------------
    // Constructores
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $datos */
    public static function pagina(array $datos = []): self
    {
        return new self(self::PAGINA, $datos);
    }

    /**
     * JSON de éxito.
     *
     * @param array<string, mixed> $datos
     * @param string|null $redireccion URL ya armada con url().
     */
    public static function exito(array $datos = [], ?string $mensaje = null, ?string $redireccion = null): self
    {
        $cuerpo = ['ok' => true, 'datos' => (object) $datos];  // (object): {} y no [] si está vacío
        if ($mensaje !== null) {
            $cuerpo['mensaje'] = $mensaje;
        }
        if ($redireccion !== null) {
            $cuerpo['redireccion'] = $redireccion;
        }
        return new self(self::JSON, $cuerpo);
    }

    /** JSON de error, con el código HTTP que corresponde. */
    public static function error(string $mensaje, int $codigo): self
    {
        return new self(self::JSON, ['ok' => false, 'mensaje' => $mensaje], $codigo);
    }

    /**
     * Faltan requisitos que se pueden saltear: el JS muestra los avisos y, si el
     * educador acepta, repite el pedido con confirmarIgual=1.
     *
     * @param list<array{tipo: string, mensaje: string}> $avisos
     */
    public static function confirmar(string $mensaje, array $avisos): self
    {
        return new self(self::JSON, [
            'ok'        => false,
            'confirmar' => true,
            'mensaje'   => $mensaje,
            'avisos'    => $avisos,
        ], 409);
    }

    /**
     * Traduce una excepción a un JSON de error. Es el único lugar con la tabla
     * excepción → código HTTP (la usan Api y Pagina):
     *
     *   SesionRequeridaException       401
     *   AccesoDenegadoException        403
     *   NoEncontradoException          404
     *   RequisitosPendientesException  409 (pide confirmar)
     *   ReglaNegocioException          422
     *   cualquier otra                 500, con un mensaje genérico
     *
     * Solo el mensaje de una ServicioException se muestra al usuario. Cualquier
     * otra (PDOException, TypeError...) puede tener rutas del disco o SQL: va
     * al error_log y el usuario ve "Ocurrió un error inesperado".
     */
    public static function desdeExcepcion(\Throwable $e): self
    {
        if ($e instanceof RequisitosPendientesException) {
            return self::confirmar($e->getMessage(), array_map(
                fn (Aviso $a) => ['tipo' => $a->tipo->value, 'mensaje' => $a->mensaje],
                $e->avisos,
            ));
        }

        $codigo = match (true) {
            $e instanceof SesionRequeridaException => 401,
            $e instanceof AccesoDenegadoException  => 403,
            $e instanceof NoEncontradoException    => 404,
            $e instanceof ServicioException        => 422,  // ReglaNegocioException y cualquier otra del Service
            default                                => 500,
        };

        if ($codigo === 500) {
            error_log('[Educarama] ' . $e::class . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return self::error(self::MENSAJE_INESPERADO, 500);
        }

        return self::error($e->getMessage(), $codigo);
    }

    /** @param string $url URL ya armada con url(). */
    public static function redireccion(string $url): self
    {
        return new self(self::REDIRECCION, url: $url, codigo: 303);
    }

    /** Un archivo del almacén; lo envía AlmacenArchivos::enviar(). */
    public static function archivo(string $rutaRelativa, string $nombreParaDescarga, bool $enLinea = false): self
    {
        return new self(self::ARCHIVO, archivo: [
            'ruta'    => $rutaRelativa,
            'nombre'  => $nombreParaDescarga,
            'enLinea' => $enLinea,
        ]);
    }

    // -----------------------------------------------------------------
    // Emitir (solo lo llaman Api y Pagina)
    // -----------------------------------------------------------------

    /**
     * Manda la respuesta al navegador y corta. Las de tipo PAGINA no se emiten:
     * sus datos los usa la página para armar el HTML.
     */
    public function emitir(): never
    {
        switch ($this->tipo) {
            case self::JSON:
                http_response_code($this->codigo);
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-store');
                echo json_encode($this->datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                exit;

            case self::REDIRECCION:
                header('Location: ' . $this->url, true, $this->codigo);
                exit;

            case self::ARCHIVO:
                try {
                    (new AlmacenArchivos())->enviar(
                        $this->archivo['ruta'],
                        $this->archivo['nombre'],
                        $this->archivo['enLinea'],
                    );
                } catch (ArchivoInvalidoException) {
                    // La fila existe pero el archivo no está en el disco: para el usuario, 404.
                    throw new NoEncontradoException('El archivo no está disponible.');
                }

            default:
                throw new \LogicException('Una respuesta de tipo página no se emite: la usa la vista.');
        }
    }
}
