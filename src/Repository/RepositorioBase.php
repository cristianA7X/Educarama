<?php
// src/Repository/RepositorioBase.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Config\Database;
use PDO;

/**
 * Base de todos los repositorios.
 *
 * Resuelve lo que se repetiría en cada uno: tener la conexión, ejecutar una
 * consulta preparada y convertir cada fila en un Model con su desdeFila().
 * El SQL lo escribe siempre cada repositorio, explícito: esta clase no arma consultas.
 *
 * Uso típico dentro de un repositorio:
 *
 *   return $this->listar('SELECT * FROM etapa WHERE id_rama = ? ORDER BY numero', [$idRama], Etapa::class);
 */
abstract class RepositorioBase
{
    protected PDO $conexion;

    /**
     * Si no se pasa una conexión, se usa la de siempre (Database::getConexion()).
     * Pasar una propia sirve para pruebas o para que varios repositorios
     * compartan una misma transacción.
     */
    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? Database::getConexion();
    }

    /**
     * Ejecuta una consulta preparada y devuelve las filas tal como las da PDO.
     * Es el único lugar que toca PDO; las subclases pueden redefinirlo (ver RepositorioEstatico).
     *
     * @param array<int|string, mixed> $params Valores para los ? (o :nombre) de la consulta
     * @return array<int, array<string, mixed>>
     */
    protected function consultar(string $sql, array $params = []): array
    {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Devuelve todas las filas de la consulta convertidas en Models.
     *
     * @template T
     * @param class-string<T> $claseModel Ej. Etapa::class; debe tener desdeFila()
     * @return T[]
     */
    protected function listar(string $sql, array $params, string $claseModel): array
    {
        return array_map(
            fn (array $fila) => $claseModel::desdeFila($fila),
            $this->consultar($sql, $params),
        );
    }

    /**
     * Devuelve el primer resultado como Model, o null si la consulta no trae filas.
     *
     * @template T
     * @param class-string<T> $claseModel
     * @return T|null
     */
    protected function buscarUno(string $sql, array $params, string $claseModel): ?object
    {
        $filas = $this->consultar($sql, $params);

        return $filas === [] ? null : $claseModel::desdeFila($filas[0]);
    }

    /**
     * Ejecuta un INSERT, UPDATE o DELETE y devuelve cuántas filas afectó.
     *
     * @param array<int|string, mixed> $params
     */
    protected function ejecutar(string $sql, array $params = []): int
    {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Ejecuta un INSERT y devuelve el id AUTO_INCREMENT que le asignó la base.
     *
     * @param array<int|string, mixed> $params
     */
    protected function insertarFila(string $sql, array $params): int
    {
        $this->ejecutar($sql, $params);

        return (int) $this->conexion->lastInsertId();
    }

    /**
     * Devuelve el id de un Model que ya está guardado. Si es null (todavía no se
     * insertó), lanza una excepción: actualizar algo sin id no tiene sentido y
     * es mejor enterarse acá que con un UPDATE que no cambia nada.
     */
    protected function exigirId(?int $id): int
    {
        if ($id === null) {
            throw new \InvalidArgumentException('El objeto no tiene id: primero hay que insertarlo.');
        }

        return $id;
    }
}
