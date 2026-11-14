<?php
// src/Model/Nomenclatura.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Un término que cambia según la rama (ej. clave 'pequenio_equipo' = 'Seisena' en LyL).
 *
 * Tabla `nomenclatura`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 * Sin id propio: la identifican clave + id_rama.
 */
final class Nomenclatura
{
    public function __construct(
        public string $clave,   // areas | competencias | pequenio_equipo
        public int    $idRama,
        public string $texto,   // Texto a mostrar
    ) {}

    /**
     * Arma el objeto desde una fila de `nomenclatura`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            clave: (string) $fila['clave'],
            idRama: (int) $fila['id_rama'],
            texto: (string) $fila['texto'],
        );
    }
}
