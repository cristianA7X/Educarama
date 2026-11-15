<?php
// src/Repository/AccionRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Accion;

/**
 * Lectura de `accion`: el catálogo oficial de acciones de cada competencia (848).
 * Rovers no tiene acciones (rama.usa_acciones = 0).
 * Tabla estática: solo lectura. Las acciones cumplidas por cada protagonista
 * están en `accion_cumplida` y van en otro repositorio.
 */
final class AccionRepository extends RepositorioEstatico
{
    public function buscarPorId(int $id): ?Accion
    {
        return $this->buscarUno('SELECT * FROM accion WHERE id = ?', [$id], Accion::class);
    }

    /** @return Accion[] Ordenadas por número. */
    public function listarPorCompetencia(int $idCompetencia): array
    {
        return $this->listar(
            'SELECT * FROM accion WHERE id_competencia = ? ORDER BY numero',
            [$idCompetencia],
            Accion::class,
        );
    }

    /**
     * Todas las acciones de una rama en una sola consulta, en el mismo orden que
     * CompetenciaRepository::listarPorRama (área, competencia, acción).
     *
     * Para pantallas que muestran todas las competencias con sus acciones: evita
     * hacer una consulta por competencia. Se agrupan en PHP por $accion->idCompetencia.
     *
     * @return Accion[]
     */
    public function listarPorRama(int $idRama): array
    {
        // El JOIN es solo para filtrar y ordenar: se devuelven únicamente las columnas de accion.
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
