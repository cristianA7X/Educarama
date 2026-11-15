<?php
// src/Model/Area.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Área de desarrollo. 1 a 4 las usan LyL, Scouts y Caminantes; 5 a 10, solo Rovers.
 *
 * Tabla `area`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 * El nombre que se muestra en cada rama está en AreaRama.
 */
final class Area
{
    public function __construct(
        public ?int $id,      // null mientras no se guardó en la base
        public string $titulo,  // Nombre general, ej. 'Salud y Bienestar'
    ) {
    }

    /**
     * Arma el objeto desde una fila de `area`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            titulo: (string) $fila['titulo'],
        );
    }
}
