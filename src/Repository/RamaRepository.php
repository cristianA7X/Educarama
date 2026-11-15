<?php // src/Repository/RamaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Rama;
use PDO;

final class RamaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function listarTodas(): array
    {
        return $this->listar('SELECT * FROM rama ORDER BY id', [], Rama::class);
    }

    public function buscarPorId(int $id): ?Rama
    {
        return $this->buscarUno('SELECT * FROM rama WHERE id = ?', [$id], Rama::class);
    }
}