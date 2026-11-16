<?php
// src/Service/Vista/UsuarioSesion.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Usuario;

/**
 * El usuario logueado, tal como lo pueden ver las vistas: sin el hash de la
 * contraseña ni el token de recuperación (reglas del proyecto: "Usuario nunca se pasa
 * entero a una vista").
 */
final class UsuarioSesion
{
    public function __construct(
        public readonly int    $id,
        public readonly string $nombre,
        public readonly string $email,
        public readonly bool   $esAdmin,
    ) {}

    public static function desdeUsuario(Usuario $usuario): self
    {
        return new self(
            id: $usuario->id ?? throw new \InvalidArgumentException('El usuario no está guardado.'),
            nombre: $usuario->nombre,
            email: $usuario->email,
            esAdmin: $usuario->esAdmin,
        );
    }
}
