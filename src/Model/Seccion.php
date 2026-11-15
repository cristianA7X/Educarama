<?php
// src/Model/Seccion.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Sección de un grupo: la Manada, Unidad, Comunidad, etc. de ese grupo en concreto.
 *
 * Tabla `seccion`. Tabla dinámica: la cargan los usuarios.
 * En el código viejo esto se llamaba 'rama'. Rama ahora es la categoría del programa.
 */
final class Seccion
{
    public function __construct(
        public ?int   $id,       // null mientras no se guardó en la base
        public string $nombre,   // Nombre propio, ej. 'Manada Seeonee'
        public int    $idGrupo,
        public int    $idRama,   // De qué rama es esta sección
    ) {}

    /**
     * Arma el objeto desde una fila de `seccion`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            nombre: (string) $fila['nombre'],
            idGrupo: (int) $fila['id_grupo'],
            idRama: (int) $fila['id_rama'],
        );
    }
}
