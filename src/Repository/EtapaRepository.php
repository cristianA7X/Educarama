<?php
// src/Repository/EtapaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Etapa;

/**
 * Lectura de `etapa`: las etapas de progresión de cada rama, con el cupo fijo
 * de competencias (competencias_etapa) que el Service usa para avisar y sugerir el pase.
 * Tabla estática: solo lectura.
 */
final class EtapaRepository extends RepositorioEstatico
{
    public function buscarPorId(int $id): ?Etapa
    {
        return $this->buscarUno('SELECT * FROM etapa WHERE id = ?', [$id], Etapa::class);
    }

    /** @return Etapa[] En orden de progresión (por número). */
    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM etapa WHERE id_rama = ? ORDER BY numero',
            [$idRama],
            Etapa::class,
        );
    }
}
