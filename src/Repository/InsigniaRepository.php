<?php
// src/Repository/InsigniaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Insignia;

/**
 * Lectura de `insignia`: el catálogo de insignias (las de cada etapa y otras, como Ciudadanía).
 * Tabla estática: solo lectura. Las otorgadas están en `insignia_otorgada` y van en otro repositorio.
 */
final class InsigniaRepository extends RepositorioEstatico
{
    public function buscarPorId(int $id): ?Insignia
    {
        return $this->buscarUno('SELECT * FROM insignia WHERE id = ?', [$id], Insignia::class);
    }

    /** @return Insignia[] Las insignias de una rama (de etapa y otras). */
    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM insignia WHERE id_rama = ? ORDER BY id',
            [$idRama],
            Insignia::class,
        );
    }

    /**
     * Las que puede recibir un protagonista de esa rama: las de la rama y las
     * generales (id_rama NULL). Primero las de etapa, en orden de etapa.
     *
     * @return Insignia[]
     */
    public function listarParaRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM insignia
              WHERE id_rama = ? OR id_rama IS NULL
              ORDER BY id_etapa IS NULL, id_etapa, id',
            [$idRama],
            Insignia::class,
        );
    }

    /**
     * La insignia que corresponde a una etapa, o null si no tiene.
     * Se usa al confirmar una etapa, para ofrecer otorgarla en la misma operación.
     */
    public function buscarPorEtapa(int $idEtapa): ?Insignia
    {
        return $this->buscarUno(
            'SELECT * FROM insignia WHERE id_etapa = ? ORDER BY id LIMIT 1',
            [$idEtapa],
            Insignia::class,
        );
    }
}
