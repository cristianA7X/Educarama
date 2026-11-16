<?php // src/Repository/AccionCumplidaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AccionCumplida;
use PDO;

final class AccionCumplidaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idProtagonista, int $idAccion): ?AccionCumplida
    {
        return $this->buscarUno(
            'SELECT * FROM accion_cumplida WHERE id_protagonista = ? AND id_accion = ?',
            [$idProtagonista, $idAccion],
            AccionCumplida::class,
        );
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT ac.*
               FROM accion_cumplida ac
               JOIN accion a      ON a.id = ac.id_accion
               JOIN competencia c ON c.id = a.id_competencia
              WHERE ac.id_protagonista = ?
              ORDER BY c.id_area, c.numero, a.numero',
            [$idProtagonista],
            AccionCumplida::class,
        );
    }

    public function listarPorProtagonistaYCompetencia(int $idProtagonista, int $idCompetencia): array
    {
        return $this->listar(
            'SELECT ac.*
               FROM accion_cumplida ac
               JOIN accion a ON a.id = ac.id_accion
              WHERE ac.id_protagonista = ? AND a.id_competencia = ?
              ORDER BY a.numero',
            [$idProtagonista, $idCompetencia],
            AccionCumplida::class,
        );
    }

    public function insertar(AccionCumplida $accionCumplida): void
    {
        $this->ejecutar(
            'INSERT INTO accion_cumplida (id_protagonista, id_accion, fecha) VALUES (?, ?, ?)',
            [$accionCumplida->idProtagonista, $accionCumplida->idAccion, $accionCumplida->fecha],
        );
    }

    public function eliminar(int $idProtagonista, int $idAccion): void
    {
        $this->ejecutar(
            'DELETE FROM accion_cumplida WHERE id_protagonista = ? AND id_accion = ?',
            [$idProtagonista, $idAccion],
        );
    }
}