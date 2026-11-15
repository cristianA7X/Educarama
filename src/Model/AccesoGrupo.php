<?php
// src/Model/AccesoGrupo.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Rol que tiene un usuario sobre un grupo.
 *
 * Tabla `acceso_grupo`. Tabla dinámica: la cargan los usuarios.
 * Un Jefe de grupo es Jefe de todas sus secciones sin filas en acceso_seccion (lo deriva el Service).
 * Sin id propio: la identifican id_usuario + id_grupo.
 */
final class AccesoGrupo
{
    public function __construct(
        public int $idUsuario,
        public int $idGrupo,
        public int $idRol,
    ) {}

    /**
     * Arma el objeto desde una fila de `acceso_grupo`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idUsuario: (int) $fila['id_usuario'],
            idGrupo: (int) $fila['id_grupo'],
            idRol: (int) $fila['id_rol'],
        );
    }
}
