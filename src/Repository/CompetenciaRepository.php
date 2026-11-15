<?php // src/Repository/CompetenciaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Competencia;
use PDO;

final class CompetenciaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Competencia
    {
        return $this->buscarUno('SELECT * FROM competencia WHERE id = ?', [$id], Competencia::class);
    }

    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM competencia WHERE id_rama = ? ORDER BY id_area, numero',
            [$idRama],
            Competencia::class,
        );
    }

    public function listarPorRamaYArea(int $idRama, int $idArea): array
    {
        return $this->listar(
            'SELECT * FROM competencia WHERE id_rama = ? AND id_area = ? ORDER BY numero',
            [$idRama, $idArea],
            Competencia::class,
        );
    }
}