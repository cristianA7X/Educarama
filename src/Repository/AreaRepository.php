<?php // src/Repository/AreaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Area;
use PDO;

final class AreaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function listarTodas(): array
    {
        return $this->listar('SELECT * FROM area ORDER BY id', [], Area::class);
    }

    public function buscarPorId(int $id): ?Area
    {
        return $this->buscarUno('SELECT * FROM area WHERE id = ?', [$id], Area::class);
    }
}