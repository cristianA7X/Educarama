<?php
// src/Repository/Vista/UsuarioConAcceso.php

declare(strict_types=1);

namespace Educarama\Repository\Vista;

use Educarama\Model\Enum\NivelRol;

/**
 * Un usuario con su rol en un grupo o en una sección, para la pantalla de
 * gestión de accesos. Sale de un JOIN acceso_* + usuario + rol.
 *
 * Clase "vista": trae solo lo que se muestra (nombre, email y rol), nunca el
 * hash de la contraseña.
 */
final class UsuarioConAcceso
{
    public function __construct(
        public readonly int      $idUsuario,
        public readonly string   $nombre,
        public readonly string   $email,
        public readonly NivelRol $nivel,
    ) {}

    public static function desdeFila(array $fila): self
    {
        return new self(
            idUsuario: (int) $fila['id_usuario'],
            nombre: (string) $fila['usuario_nombre'],
            email: (string) $fila['usuario_email'],
            nivel: NivelRol::from((int) $fila['rol_nivel']),
        );
    }
}
