<?php
// src/Repository/RepositorioEstatico.php

declare(strict_types=1);

namespace Educarama\Repository;

/**
 * Base de los repositorios de tablas estáticas (rama, area, competencia, etapa, rol...).
 *
 * Dos diferencias con RepositorioBase:
 *
 * 1. Solo lectura: estos repositorios no tienen métodos para insertar, modificar
 *    ni borrar. Las tablas estáticas las cargan los devs desde database/educarama.sql.
 *
 * 2. Caché en memoria: como esos datos no cambian mientras la página se ejecuta,
 *     la misma consulta con los mismos parámetros va a la base una sola vez por pedido.
 *    Ej.: una página que pide la nomenclatura de la rama 20 veces hace 1 sola consulta.

 * La caché guarda las FILAS, no los Models: cada llamada arma objetos nuevos.
 * Así, si alguien modifica un Model que recibió ($rama->titulo = 'x'), no
 * contamina lo que reciben las llamadas siguientes.
 *
 * La caché dura lo que dura el pedido HTTP (es una propiedad static de PHP):
 * no hay que invalidarla nunca.
 */
abstract class RepositorioEstatico extends RepositorioBase
{
    /**
     * Filas ya consultadas, por consulta + parámetros.
     * Es static para que la compartan todas las instancias: dos "new RamaRepository()"
     * en la misma página usan la misma caché.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    private static array $cache = [];

    protected function consultar(string $sql, array $params = []): array
    {
        $clave = $sql . '|' . json_encode($params);

        // ??= ejecuta la consulta solo si la clave todavía no está guardada.
        return self::$cache[$clave] ??= parent::consultar($sql, $params);
    }
}
