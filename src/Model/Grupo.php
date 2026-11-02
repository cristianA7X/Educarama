<?php
// src/Model/Grupo.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Grupo scout (ej. 'Posta de Pardo', código 64).
 *
 * Tabla `grupo`. Tabla dinámica: la cargan los usuarios.
 */
final class Grupo
{
    public function __construct(
        public ?int   $id,      // null mientras no se guardó en la base
        public string $codigo,  // Número oficial del grupo
        public string $nombre,
    ) {}

    /**
     * Arma el objeto desde una fila de `grupo`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            codigo: (string) $fila['codigo'],
            nombre: (string) $fila['nombre'],
        );
    }
}
