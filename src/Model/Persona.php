<?php
// src/Model/Persona.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Datos personales compartidos por protagonistas y familiares.
 *
 * Tabla `persona`. Tabla dinámica: la cargan los usuarios.
 */
final class Persona
{
    public function __construct(
        public ?int    $id,               // null mientras no se guardó en la base
        public int     $dni,              // Único en toda la base
        public string  $nombre,
        public string  $apellido,
        public ?string $fechaNacimiento,  // Formato 'AAAA-MM-DD'
    ) {}

    /**
     * Arma el objeto desde una fila de `persona`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            dni: (int) $fila['dni'],
            nombre: (string) $fila['nombre'],
            apellido: (string) $fila['apellido'],
            fechaNacimiento: $fila['fecha_nacimiento'] === null ? null : (string) $fila['fecha_nacimiento'],
        );
    }
}
