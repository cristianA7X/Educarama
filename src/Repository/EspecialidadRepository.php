<?php
// src/Repository/EspecialidadRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Especialidad;

/**
 * Lectura de `especialidad`: el catálogo de especialidades, agrupadas por área.
 * Solo las usan las ramas con rama.usa_especialidades (LyL y Scouts); eso lo valida el Service.
 * Tabla estática: solo lectura.
 */
final class EspecialidadRepository extends RepositorioEstatico
{
    public function buscarPorId(int $id): ?Especialidad
    {
        return $this->buscarUno('SELECT * FROM especialidad WHERE id = ?', [$id], Especialidad::class);
    }

    /** @return Especialidad[] Ordenadas por área y número. */
    public function listarTodas(): array
    {
        return $this->listar('SELECT * FROM especialidad ORDER BY id_area, numero', [], Especialidad::class);
    }

    /** @return Especialidad[] Ordenadas por número. */
    public function listarPorArea(int $idArea): array
    {
        return $this->listar(
            'SELECT * FROM especialidad WHERE id_area = ? ORDER BY numero',
            [$idArea],
            Especialidad::class,
        );
    }
}
