<?php
// src/Repository/TareaEtapaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\TareaEtapa;

/**
 * Lectura de `tarea_etapa`: las tareas que pide cada etapa.
 * Tabla estática: solo lectura. Las cumplidas por cada protagonista están
 * en `tarea_etapa_cumplida` y van en otro repositorio.
 */
final class TareaEtapaRepository extends RepositorioEstatico
{
    public function buscarPorId(int $id): ?TareaEtapa
    {
        return $this->buscarUno('SELECT * FROM tarea_etapa WHERE id = ?', [$id], TareaEtapa::class);
    }

    /** @return TareaEtapa[] Ordenadas por número. */
    public function listarPorEtapa(int $idEtapa): array
    {
        return $this->listar(
            'SELECT * FROM tarea_etapa WHERE id_etapa = ? ORDER BY numero',
            [$idEtapa],
            TareaEtapa::class,
        );
    }
}
