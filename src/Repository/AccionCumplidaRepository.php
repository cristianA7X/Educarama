<?php
// src/Repository/AccionCumplidaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AccionCumplida;

/**
 * Lectura y escritura de `accion_cumplida`: las acciones que un protagonista cumplió.
 *
 * Tabla de progresión: sin fila = pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: cada fila se identifica por id_protagonista + id_accion.
 */
final class AccionCumplidaRepository extends RepositorioBase
{
    public function buscar(int $idProtagonista, int $idAccion): ?AccionCumplida
    {
        return $this->buscarUno(
            'SELECT * FROM accion_cumplida WHERE id_protagonista = ? AND id_accion = ?',
            [$idProtagonista, $idAccion],
            AccionCumplida::class,
        );
    }

    /**
     * Todas las acciones cumplidas por el protagonista, en el orden del catálogo
     * (área, competencia, acción), igual que AccionRepository::listarPorRama.
     *
     * @return AccionCumplida[]
     */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        // El JOIN es solo para ordenar: se devuelven únicamente las columnas de accion_cumplida.
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

    /**
     * Las acciones cumplidas de UNA competencia. Comparándolas con
     * AccionRepository::listarPorCompetencia, el Service sabe si las cumplió todas.
     * (El legacy contaba las acciones de todas las competencias: este filtro corrige ese bug.)
     *
     * @return AccionCumplida[]
     */
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

    /** Registra que el protagonista cumplió la acción, en la fecha que trae el objeto. */
    public function insertar(AccionCumplida $accionCumplida): void
    {
        $this->ejecutar(
            'INSERT INTO accion_cumplida (id_protagonista, id_accion, fecha) VALUES (?, ?, ?)',
            [$accionCumplida->idProtagonista, $accionCumplida->idAccion, $accionCumplida->fecha],
        );
    }

    /** Borra la fila: la acción vuelve a quedar pendiente. */
    public function eliminar(int $idProtagonista, int $idAccion): void
    {
        $this->ejecutar(
            'DELETE FROM accion_cumplida WHERE id_protagonista = ? AND id_accion = ?',
            [$idProtagonista, $idAccion],
        );
    }
}
