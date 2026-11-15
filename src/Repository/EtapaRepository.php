<?php // src/Repository/EtapaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Etapa;
use PDO;

final class EtapaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Etapa
    {
        return $this->buscarUno('SELECT * FROM etapa WHERE id = ?', [$id], Etapa::class);
    }

    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM etapa WHERE id_rama = ? ORDER BY numero',
            [$idRama],
            Etapa::class,
        );
    }
}