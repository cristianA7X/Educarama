<?php // src/Repository/AreaRamaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AreaRama;
use PDO;

final class AreaRamaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM area_rama WHERE id_rama = ? ORDER BY id_area',
            [$idRama],
            AreaRama::class,
        );
    }
}