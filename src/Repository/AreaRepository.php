<?php
// src/Repository/AreaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Area;

/**
 * Lectura de `area`: las 10 áreas con su nombre general.
 * Para el nombre que tiene un área dentro de una rama, ver AreaRamaRepository.
 * Tabla estática: solo lectura.
 */
final class AreaRepository extends RepositorioEstatico
{
    /** @return Area[] */
    public function listarTodas(): array
    {
        return $this->listar('SELECT * FROM area ORDER BY id', [], Area::class);
    }

    public function buscarPorId(int $id): ?Area
    {
        return $this->buscarUno('SELECT * FROM area WHERE id = ?', [$id], Area::class);
    }
}
