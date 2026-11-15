<?php // src/Repository/RepositorioBase.php
declare(strict_types=1);

namespace Educarama\Repository;

use PDO;

abstract class RepositorioBase
{
    public function __construct(
        protected readonly PDO $conexion
    ) {}

    protected function consultar(string $sql, array $params = []): array
    {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function listar(string $sql, array $params, string $claseModel): array
    {
        return array_map(
            fn (array $fila) => $claseModel::desdeFila($fila),
            $this->consultar($sql, $params),
        );
    }

    protected function buscarUno(string $sql, array $params, string $claseModel): ?object
    {
        $filas = $this->consultar($sql, $params);
        return $filas === [] ? null : $claseModel::desdeFila($filas[0]);
    }

    protected function ejecutar(string $sql, array $params = []): int
    {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function insertarFila(string $sql, array $params): int
    {
        $this->ejecutar($sql, $params);
        return (int) $this->conexion->lastInsertId();
    }

    protected function exigirId(?int $id): int
    {
        if ($id === null) {
            throw new \InvalidArgumentException('El objeto no tiene id: primero hay que insertarlo.');
        }
        return $id;
    }
}