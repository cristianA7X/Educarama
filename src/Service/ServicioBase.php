<?php
// src/Service/ServicioBase.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Config\Database;
use PDO;
use Throwable;

/**
 * Base de todos los Services.
 *
 * Un Service aplica las reglas de negocio y usa los repositorios para leer y guardar.
 * No toca $_SESSION ni imprime HTML: recibe datos (incluido el id del usuario
 * actual) y devuelve Models, o lanza una excepción de Educarama\Service\Excepcion.
 *
 * Cada Service recibe sus repositorios en el constructor como parámetros
 * opcionales; si no se pasan, crea los de siempre:
 *
 *   public function __construct(?PatrullaRepository $patrullas = null, ...)
 *   {
 *       parent::__construct();
 *       $this->patrullas = $patrullas ?? new PatrullaRepository();
 *   }
 */
abstract class ServicioBase
{
    protected PDO $conexion;

    /**
     * La conexión tiene que ser la misma que usan los repositorios: si no, la
     * transacción no los abarca. Por defecto todos usan Database::getConexion().
     */
    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? Database::getConexion();
    }

    /**
     * Ejecuta $operacion dentro de una transacción: si termina bien hace commit,
     * y si lanza cualquier excepción hace rollback y la vuelve a lanzar.
     * Devuelve lo que devuelva $operacion.
     *
     *   $id = $this->transaccion(function () use ($datos) {
     *       $id = $this->grupos->insertar(...);
     *       $this->secciones->insertar(...);
     *       return $id;
     *   });
     *
     * Si ya hay una transacción abierta (un Service que llama a otro), no abre
     * otra: MySQL no tiene transacciones anidadas. La operación queda dentro de
     * la de afuera, que es la que decide el commit o el rollback.
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    protected function transaccion(callable $operacion): mixed
    {
        if ($this->conexion->inTransaction()) {
            return $operacion();
        }

        $this->conexion->beginTransaction();

        try {
            $resultado = $operacion();
            $this->conexion->commit();

            return $resultado;
        } catch (Throwable $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }
}
