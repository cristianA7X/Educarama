<?php
// src/Model/AccesoSeccion.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Rol que tiene un usuario sobre una sección.
 *
 * Tabla `acceso_seccion`. Tabla dinámica: la cargan los usuarios.
 * Sin id propio: la identifican id_usuario + id_seccion.
 */
final class AccesoSeccion
{
    public function __construct(
        public int $idUsuario,
        public int $idSeccion,
        public int $idRol,
    ) {}

    /**
     * Arma el objeto desde una fila de `acceso_seccion`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idUsuario: (int) $fila['id_usuario'],
            idSeccion: (int) $fila['id_seccion'],
            idRol: (int) $fila['id_rol'],
        );
    }
}
