<?php
// src/Model/Patrulla.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Pequeño equipo de una sección (Seisena, Patrulla o Equipo según la rama).
 *
 * Tabla `patrulla`. Tabla dinámica: la cargan los usuarios.
 */
final class Patrulla
{
    public function __construct(
        public ?int   $id,         // null mientras no se guardó en la base
        public string $nombre,
        public int    $idSeccion,
    ) {}

    /**
     * Arma el objeto desde una fila de `patrulla`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            nombre: (string) $fila['nombre'],
            idSeccion: (int) $fila['id_seccion'],
        );
    }
}
