<?php
// src/Repository/CompetenciaProtagonistaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\CompetenciaProtagonista;

/**
 * Lectura y escritura de `competencia_protagonista`: las competencias que un
 * protagonista eligió (en proceso) o ya desarrolló.
 *
 * Tabla de progresión: sin fila = pendiente. La fila la crea un educador al confirmar,
 * y pasar de 'en proceso' a 'desarrollada' es un actualizar().
 * Sin id propio: cada fila se identifica por id_protagonista + id_competencia.
 *
 * Contar cuántas tiene desarrolladas y compararlo con la etapa lo hace el Service.
 */
final class CompetenciaProtagonistaRepository extends RepositorioBase
{
    public function buscar(int $idProtagonista, int $idCompetencia): ?CompetenciaProtagonista
    {
        return $this->buscarUno(
            'SELECT * FROM competencia_protagonista WHERE id_protagonista = ? AND id_competencia = ?',
            [$idProtagonista, $idCompetencia],
            CompetenciaProtagonista::class,
        );
    }

    /**
     * Todas las competencias del protagonista (en proceso y desarrolladas),
     * en el orden del catálogo: área y número.
     *
     * @return CompetenciaProtagonista[]
     */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        // El JOIN es solo para ordenar: se devuelven únicamente las columnas de competencia_protagonista.
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

    /**
     * Registra que el protagonista eligió (o desarrolló) una competencia.
     * La fecha viene en el objeto: el Service pone la de hoy o la que cargue el educador.
     */
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

    /** Cambia el estado (ej. en proceso → desarrollada) y la fecha del cambio. */
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

    /** Borra la fila: la competencia vuelve a quedar pendiente. */
    public function eliminar(int $idProtagonista, int $idCompetencia): void
    {
        $this->ejecutar(
            'DELETE FROM competencia_protagonista WHERE id_protagonista = ? AND id_competencia = ?',
            [$idProtagonista, $idCompetencia],
        );
    }
}
