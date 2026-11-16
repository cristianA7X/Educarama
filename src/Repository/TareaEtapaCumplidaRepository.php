<?php // src/Repository/TareaEtapaCumplidaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\TareaEtapaCumplida;
use PDO;

final class TareaEtapaCumplidaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idProtagonista, int $idTareaEtapa): ?TareaEtapaCumplida
    {
        return $this->buscarUno(
            'SELECT * FROM tarea_etapa_cumplida WHERE id_protagonista = ? AND id_tarea_etapa = ?',
            [$idProtagonista, $idTareaEtapa],
            TareaEtapaCumplida::class,
        );
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT tc.*
               FROM tarea_etapa_cumplida tc
               JOIN tarea_etapa t ON t.id = tc.id_tarea_etapa
              WHERE tc.id_protagonista = ?
              ORDER BY t.id_etapa, t.numero',
            [$idProtagonista],
            TareaEtapaCumplida::class,
        );
    }

    public function listarPorProtagonistaYEtapa(int $idProtagonista, int $idEtapa): array
    {
        return $this->listar(
            'SELECT tc.*
               FROM tarea_etapa_cumplida tc
               JOIN tarea_etapa t ON t.id = tc.id_tarea_etapa
              WHERE tc.id_protagonista = ? AND t.id_etapa = ?
              ORDER BY t.numero',
            [$idProtagonista, $idEtapa],
            TareaEtapaCumplida::class,
        );
    }

    public function insertar(TareaEtapaCumplida $tareaCumplida): void
    {
        $this->ejecutar(
            'INSERT INTO tarea_etapa_cumplida (id_protagonista, id_tarea_etapa, fecha) VALUES (?, ?, ?)',
            [$tareaCumplida->idProtagonista, $tareaCumplida->idTareaEtapa, $tareaCumplida->fecha],
        );
    }

    public function eliminar(int $idProtagonista, int $idTareaEtapa): void
    {
        $this->ejecutar(
            'DELETE FROM tarea_etapa_cumplida WHERE id_protagonista = ? AND id_tarea_etapa = ?',
            [$idProtagonista, $idTareaEtapa],
        );
    }
}