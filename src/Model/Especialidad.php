<?php
// src/Model/Especialidad.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Especialidad (solo LyL y Scouts), agrupada por área de desarrollo 1 a 4.
 *
 * Tabla `especialidad`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 */
final class Especialidad
{
    public function __construct(
        public ?int   $id,      // null mientras no se guardó en la base
        public int    $idArea,
        public int    $numero,
        public string $titulo,
    ) {}

    /**
     * Arma el objeto desde una fila de `especialidad`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idArea: (int) $fila['id_area'],
            numero: (int) $fila['numero'],
            titulo: (string) $fila['titulo'],
        );
    }
}
