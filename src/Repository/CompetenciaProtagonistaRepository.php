<?php // src/Repository/CompetenciaProtagonistaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\CompetenciaProtagonista;
use PDO;

final class CompetenciaProtagonistaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idProtagonista, int $idCompetencia): ?CompetenciaProtagonista
    {
        return $this->buscarUno(
            'SELECT * FROM competencia_protagonista WHERE id_protagonista = ? AND id_competencia = ?',
            [$idProtagonista, $idCompetencia],
            CompetenciaProtagonista::class,
        );
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT cp.*
               FROM competencia_protagonista cp
               JOIN competencia c ON c.id = cp.id_competencia
              WHERE cp.id_protagonista = ?
              ORDER BY c.id_area, c.numero',
            [$idProtagonista],
            CompetenciaProtagonista::class,
        );
    }

    public function insertar(CompetenciaProtagonista $cp): void
    {
        $this->ejecutar(
            'INSERT INTO competencia_protagonista (id_protagonista, id_competencia, id_etapa, estado, fecha)
             VALUES (:id_protagonista, :id_competencia, :id_etapa, :estado, :fecha)',
            [
                'id_protagonista' => $cp->idProtagonista,
                'id_competencia'  => $cp->idCompetencia,
                'id_etapa'        => $cp->idEtapa,
                'estado'          => $cp->estado->value,
                'fecha'           => $cp->fecha,
            ],
        );
    }

    public function actualizar(CompetenciaProtagonista $cp): void
    {
        $this->ejecutar(
            'UPDATE competencia_protagonista
                SET estado = :estado, fecha = :fecha
              WHERE id_protagonista = :id_protagonista AND id_competencia = :id_competencia',
            [
                'estado'          => $cp->estado->value,
                'fecha'           => $cp->fecha,
                'id_protagonista' => $cp->idProtagonista,
                'id_competencia'  => $cp->idCompetencia,
            ],
        );
    }

    public function eliminar(int $idProtagonista, int $idCompetencia): void
    {
        $this->ejecutar(
            'DELETE FROM competencia_protagonista WHERE id_protagonista = ? AND id_competencia = ?',
            [$idProtagonista, $idCompetencia],
        );
    }
}