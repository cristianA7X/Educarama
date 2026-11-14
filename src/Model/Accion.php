<?php
// src/Model/Accion.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Acción que ayuda a medir una competencia (Rovers no tiene acciones).
 *
 * Tabla `accion`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 */
final class Accion
{
    public function __construct(
        public ?int   $id,             // null mientras no se guardó en la base
        public int    $idCompetencia,
        public int    $numero,
        public string $descripcion,
        public bool   $esOpcional,     // Solo se usa en Lobatos y Lobeznas
    ) {}

    /**
     * Arma el objeto desde una fila de `accion`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idCompetencia: (int) $fila['id_competencia'],
            numero: (int) $fila['numero'],
            descripcion: (string) $fila['descripcion'],
            esOpcional: (bool) $fila['es_opcional'],
        );
    }
}
