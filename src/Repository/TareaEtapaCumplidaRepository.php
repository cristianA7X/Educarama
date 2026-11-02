<?php
// src/Repository/TareaEtapaCumplidaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\TareaEtapaCumplida;

/**
 * Lectura y escritura de `tarea_etapa_cumplida`: las tareas de etapa que un protagonista cumplió.
 *
 * Tabla de progresión: sin fila = pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: cada fila se identifica por id_protagonista + id_tarea_etapa.
 */
final class TareaEtapaCumplidaRepository extends RepositorioBase
{
    public function buscar(int $idProtagonista, int $idTareaEtapa): ?TareaEtapaCumplida
    {
        return $this->buscarUno(
            'SELECT * FROM tarea_etapa_cumplida WHERE id_protagonista = ? AND id_tarea_etapa = ?',
            [$idProtagonista, $idTareaEtapa],
            TareaEtapaCumplida::class,
        );
    }

    /**
     * Todas las tareas cumplidas por el protagonista, ordenadas por etapa y número.
     *
     * @return TareaEtapaCumplida[]
     */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        // El JOIN es solo para ordenar: se devuelven únicamente las columnas de tarea_etapa_cumplida.
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

    /**
     * Las tareas cumplidas de UNA etapa. Comparándolas con
     * TareaEtapaRepository::listarPorEtapa, el Service sabe cuáles adeuda.
     *
     * @return TareaEtapaCumplida[]
     */
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

    /** Registra que el protagonista cumplió la tarea, en la fecha que trae el objeto. */
    public function insertar(TareaEtapaCumplida $tareaCumplida): void
    {
        $this->ejecutar(
            'INSERT INTO tarea_etapa_cumplida (id_protagonista, id_tarea_etapa, fecha) VALUES (?, ?, ?)',
            [$tareaCumplida->idProtagonista, $tareaCumplida->idTareaEtapa, $tareaCumplida->fecha],
        );
    }

    /** Borra la fila: la tarea vuelve a quedar pendiente. */
    public function eliminar(int $idProtagonista, int $idTareaEtapa): void
    {
        $this->ejecutar(
            'DELETE FROM tarea_etapa_cumplida WHERE id_protagonista = ? AND id_tarea_etapa = ?',
            [$idProtagonista, $idTareaEtapa],
        );
    }
}
