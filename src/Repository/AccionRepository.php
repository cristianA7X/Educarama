<?php // src/Repository/AccionRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Accion;
use PDO;

final class AccionRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Accion
    {
        return $this->buscarUno('SELECT * FROM accion WHERE id = ?', [$id], Accion::class);
    }

    public function listarPorCompetencia(int $idCompetencia): array
    {
        return $this->listar(
            'SELECT * FROM accion WHERE id_competencia = ? ORDER BY numero',
            [$idCompetencia],
            Accion::class,
        );
    }

    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT a.*
               FROM accion a
               JOIN competencia c ON c.id = a.id_competencia
              WHERE c.id_rama = ?
              ORDER BY c.id_area, c.numero, a.numero',
            [$idRama],
            Accion::class,
        );
    }
}