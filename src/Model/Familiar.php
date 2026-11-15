<?php
// src/Model/Familiar.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Familiar o adulto responsable de uno o más protagonistas.
 *
 * Tabla `familiar`. Tabla dinámica: la cargan los usuarios.
 */
final class Familiar
{
    public function __construct(
        public ?int    $id,                // null mientras no se guardó en la base
        public int     $idPersona,
        public ?string $telefonoContacto,  // Texto: puede tener +, espacios o guiones
        public ?string $emailContacto,
    ) {}

    /**
     * Arma el objeto desde una fila de `familiar`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idPersona: (int) $fila['id_persona'],
            telefonoContacto: $fila['telefono_contacto'] === null ? null : (string) $fila['telefono_contacto'],
            emailContacto: $fila['email_contacto'] === null ? null : (string) $fila['email_contacto'],
        );
    }
}
