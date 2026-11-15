<?php
// src/Repository/RamaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Rama;

/**
 * Lectura de `rama`: las 4 ramas del programa (LyL, Scouts, Caminantes, Rovers).
 * Tabla estática: solo lectura.
 */
final class RamaRepository extends RepositorioEstatico
{
    /** @return Rama[] Las 4 ramas, en orden (1 LyL … 4 Rovers). */
    public function listarTodas(): array
    {
        return $this->listar('SELECT * FROM rama ORDER BY id', [], Rama::class);
    }

    public function buscarPorId(int $id): ?Rama
    {
        return $this->buscarUno('SELECT * FROM rama WHERE id = ?', [$id], Rama::class);
    }
}
