<?php
// src/Model/Competencia.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Competencia educativa de una rama, dentro de un área.
 *
 * Tabla `competencia`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 */
final class Competencia
{
    public function __construct(
        public ?int   $id,           // null mientras no se guardó en la base
        public int    $idRama,
        public int    $idArea,
        public int    $numero,       // Número oficial. Rovers numera desde 1 en cada área
        public string $descripcion,
    ) {}

    /**
     * Arma el objeto desde una fila de `competencia`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idRama: (int) $fila['id_rama'],
            idArea: (int) $fila['id_area'],
            numero: (int) $fila['numero'],
            descripcion: (string) $fila['descripcion'],
        );
    }
}
