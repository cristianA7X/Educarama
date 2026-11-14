<?php
// src/Repository/AreaRamaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AreaRama;

/**
 * Lectura de `area_rama`: qué áreas usa cada rama y con qué nombre se muestran.
 * Ej.: LyL, Scouts y Caminantes usan las áreas 1–4; Rovers, las 5–10.
 * Tabla estática: solo lectura.
 */
final class AreaRamaRepository extends RepositorioEstatico
{
    /**
     * Las áreas de una rama, con el nombre que tienen en esa rama.
     * Sirve, por ejemplo, para armar las pestañas de gestionar_competencias.
     *
     * @return AreaRama[] Ordenadas por id de área.
     */
    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM area_rama WHERE id_rama = ? ORDER BY id_area',
            [$idRama],
            AreaRama::class,
        );
    }
}
