<?php
// src/Model/Rol.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Rol de acceso: Supervisor, Ayudante, Responsable o Jefe.
 *
 * Tabla `rol`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 */
final class Rol
{
    public function __construct(
        public ?int   $id,      // null mientras no se guardó en la base
        public string $nombre,
        public int    $nivel,   // 1 a 4; ver Enum\NivelRol
    ) {}

    /**
     * Arma el objeto desde una fila de `rol`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            nombre: (string) $fila['nombre'],
            nivel: (int) $fila['nivel'],
        );
    }
}
