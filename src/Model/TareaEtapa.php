<?php
// src/Model/TareaEtapa.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Requisito extra de una etapa, aparte de las competencias (ej. 'Participa de una Descubierta').
 *
 * Tabla `tarea_etapa`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 */
final class TareaEtapa
{
    public function __construct(
        public ?int   $id,           // null mientras no se guardó en la base
        public int    $idEtapa,
        public int    $numero,
        public string $descripcion,
    ) {}

    /**
     * Arma el objeto desde una fila de `tarea_etapa`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idEtapa: (int) $fila['id_etapa'],
            numero: (int) $fila['numero'],
            descripcion: (string) $fila['descripcion'],
        );
    }
}
