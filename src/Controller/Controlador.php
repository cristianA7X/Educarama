<?php
// src/Controller/Controlador.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\UsuarioService;
use Educarama\Service\Vista\UsuarioSesion;

/**
 * Base de todos los Controllers.
 *
 * Un Controller traduce un pedido HTTP a una llamada al Service y el resultado
 * a una Respuesta:
 *
 *   - Recibe arrays ($_GET, $_POST, $_FILES): nunca lee las superglobales él mismo,
 *     así se puede probar por CLI pasándole arrays armados a mano.
 *   - Saca el id del usuario de UsuarioService y se lo pasa al Service.
 *   - NO aplica reglas de negocio (eso es del Service) ni manda headers (eso es
 *     de Api y Pagina). El CSRF lo controla Api antes de llamarlo.
 *   - Las excepciones de los Services no se atrapan acá: suben hasta Api o
 *     Pagina, que las traducen a un código HTTP. Solo se atrapan cuando el
 *     Controller tiene que hacer algo distinto con ellas.
 */
abstract class Controlador
{
    public function __construct(
        protected readonly UsuarioService $usuarioService
    ) {}

    /** El usuario logueado; si no hay, SesionRequeridaException (Api: 401, Pagina: al login). */
    protected function usuario(): UsuarioSesion
    {
        return $this->usuarioService->exigirSesion();
    }

    // -----------------------------------------------------------------
    // Lectura de datos del pedido
    //
    // Lo que llega por GET o POST es siempre texto (o un array, si alguien
    // manda campo[]=...). Con strict_types, pasarle un array a un Service que
    // espera string sería un TypeError (500), así que se normaliza acá.
    // -----------------------------------------------------------------

    /** Texto tal cual llegó (el Service lo recorta y valida); '' si falta o no es texto. */
    protected static function texto(array $datos, string $clave): string
    {
        $valor = $datos[$clave] ?? '';
        return is_string($valor) ? $valor : '';
    }

    /**
     * Un id u otro entero positivo obligatorio. Si falta o no es un número, es
     * un pedido mal armado (no un error del usuario): mensaje genérico.
     *
     * @throws ReglaNegocioException
     */
    protected static function entero(array $datos, string $clave): int
    {
        return self::enteroOpcional($datos, $clave)
            ?? throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.');
    }

    /** Un entero positivo, o null si no vino o vino vacío. */
    protected static function enteroOpcional(array $datos, string $clave): ?int
    {
        $valor = $datos[$clave] ?? null;
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_int($valor)) {
            return $valor > 0 ? $valor : null;
        }
        if (is_string($valor) && ctype_digit($valor) && strlen($valor) <= 10 && (int) $valor > 0) {
            return (int) $valor;
        }
        throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.');
    }

    /**
     * Lista de ids de casillas con el mismo nombre (name="secciones[]").
     * Si falta, lista vacía. Si algún valor no es un entero positivo, es un
     * pedido mal armado: mensaje genérico.
     *
     * @return int[]
     * @throws ReglaNegocioException
     */
    protected static function listaDeEnteros(array $datos, string $clave): array
    {
        $valores = $datos[$clave] ?? [];
        if (!is_array($valores)) {
            throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.');
        }

        return array_values(array_map(
            fn ($v) => self::entero(['v' => $v], 'v'),
            $valores,
        ));
    }

    /**
     * DNI tal como lo escribe la gente: acepta puntos y espacios ("12.345.678").
     * Si no es un número, 0: el Service lo rechaza con "El DNI tiene que ser un número positivo."
     */
    protected static function dni(array $datos, string $clave = 'dni'): int
    {
        $texto = str_replace(['.', ' '], '', self::texto($datos, $clave));
        return ctype_digit($texto) && strlen($texto) <= 9 ? (int) $texto : 0;
    }

    /** Fecha opcional 'AAAA-MM-DD' (la del <input type="date">); vacía = null. La valida el Service. */
    protected static function fechaOpcional(array $datos, string $clave): ?string
    {
        $texto = trim(self::texto($datos, $clave));
        return $texto === '' ? null : $texto;
    }

    /** Casilla o bandera: "1", "true", "on" → true; cualquier otra cosa → false. */
    protected static function booleano(array $datos, string $clave): bool
    {
        $valor = $datos[$clave] ?? null;
        return in_array($valor, ['1', 'true', 'on', 1, true], true);
    }

    // -----------------------------------------------------------------
    // Redirecciones seguras
    // -----------------------------------------------------------------

    /**
     * Valida una ruta "volver" que llega del navegador. Solo acepta páginas del
     * proyecto (views/pages/*.php, con query string opcional): así nadie puede
     * armar un link al login que, después de entrar, mande a otro sitio.
     *
     * @return string|null La ruta relativa (para pasarla a url()), o null si no sirve.
     */
    protected static function rutaInterna(string $ruta): ?string
    {
        return preg_match('~^views/pages/[a-z0-9_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$~', $ruta) === 1 ? $ruta : null;
    }
}
