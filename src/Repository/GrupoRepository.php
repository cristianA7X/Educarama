<?php // src/Repository/GrupoRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Grupo;
use PDO;

final class GrupoRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Grupo
    {
        return $this->buscarUno('SELECT * FROM grupo WHERE id = ?', [$id], Grupo::class);
    }

    public function buscarPorCodigo(string $codigo): ?Grupo
    {
        return $this->buscarUno('SELECT * FROM grupo WHERE codigo = ?', [$codigo], Grupo::class);
    }

    public function insertar(Grupo $grupo): int
    {
        $grupo->id = $this->insertarFila(
            'INSERT INTO grupo (codigo, nombre) VALUES (:codigo, :nombre)',
            ['codigo' => $grupo->codigo, 'nombre' => $grupo->nombre],
        );
        return $grupo->id;
    }

    public function actualizar(Grupo $grupo): void
    {
        $this->ejecutar(
            'UPDATE grupo SET codigo = :codigo, nombre = :nombre WHERE id = :id',
            ['codigo' => $grupo->codigo, 'nombre' => $grupo->nombre, 'id' => $this->exigirId($grupo->id)],
        );
    }
}