<?php
// src/Model/Usuario.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Persona que entra al sistema (educador/a). Distinto de Protagonista.
 *
 * Tabla `usuario`. Tabla dinámica: la cargan los usuarios.
 * Nunca mandar este objeto entero a una vista: tiene el hash de la contraseña.
 */
final class Usuario
{
    public function __construct(
        public ?int    $id,                 // null mientras no se guardó en la base
        public string  $nombre,
        public string  $email,              // Único
        public string  $contrasenia,        // Hash de password_hash(); nunca la contraseña real
        public bool    $esAdmin,            // Dev que aprueba solicitudes de alta
        public bool    $emailValidado,      // Validación con Cruz del Sur (por ahora siempre false)
        public ?string $tokenRecuperacion,  // Código para recuperar la contraseña
    ) {}

    /**
     * Arma el objeto desde una fila de `usuario`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            nombre: (string) $fila['nombre'],
            email: (string) $fila['email'],
            contrasenia: (string) $fila['contrasenia'],
            esAdmin: (bool) $fila['es_admin'],
            emailValidado: (bool) $fila['email_validado'],
            tokenRecuperacion: $fila['token_recuperacion'] === null ? null : (string) $fila['token_recuperacion'],
        );
    }
}
