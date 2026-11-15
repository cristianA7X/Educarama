<?php // src/Repository/TareaEtapaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\TareaEtapa;
use PDO;

final class TareaEtapaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?TareaEtapa
    {
        return $this->buscarUno('SELECT * FROM tarea_etapa WHERE id = ?', [$id], TareaEtapa::class);
    }

    public function listarPorEtapa(int $idEtapa): array
    {
        return $this->listar(
            'SELECT * FROM tarea_etapa WHERE id_etapa = ? ORDER BY numero',
            [$idEtapa],
            TareaEtapa::class,
        );
    }
}