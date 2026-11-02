<?php
// src/Model/AreaRama.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Qué áreas usa cada rama y con qué nombre se muestran (ej. 'Sendero de la Paz' en LyL).
 *
 * Tabla `area_rama`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 * Sin id propio: la identifican id_area + id_rama.
 */
final class AreaRama
{
    public function __construct(
        public int    $idArea,
        public int    $idRama,
        public string $titulo,  // Nombre visible en esa rama
    ) {}

    /**
     * Arma el objeto desde una fila de `area_rama`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idArea: (int) $fila['id_area'],
            idRama: (int) $fila['id_rama'],
            titulo: (string) $fila['titulo'],
        );
    }
}
