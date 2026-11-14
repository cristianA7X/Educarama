<?php
// src/Repository/CompetenciaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Competencia;

/**
 * Lectura de `competencia`: el catálogo oficial (186 competencias).
 * Tabla estática: solo lectura. Lo que eligió o desarrolló cada protagonista
 * está en `competencia_protagonista` y va en otro repositorio.
 */
final class CompetenciaRepository extends RepositorioEstatico
{
    public function buscarPorId(int $id): ?Competencia
    {
        return $this->buscarUno('SELECT * FROM competencia WHERE id = ?', [$id], Competencia::class);
    }

    /**
     * Todas las competencias de una rama, ordenadas por área y número
     * (Rovers numera desde 1 dentro de cada área, por eso el área va primero).
     *
     * @return Competencia[]
     */
    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM competencia WHERE id_rama = ? ORDER BY id_area, numero',
            [$idRama],
            Competencia::class,
        );
    }

    /** @return Competencia[] Ordenadas por número. */
    public function listarPorRamaYArea(int $idRama, int $idArea): array
    {
        return $this->listar(
            'SELECT * FROM competencia WHERE id_rama = ? AND id_area = ? ORDER BY numero',
            [$idRama, $idArea],
            Competencia::class,
        );
    }
}
