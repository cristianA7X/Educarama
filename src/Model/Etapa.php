<?php
// src/Model/Etapa.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Etapa de progresión de una rama (ej. Pata Tierna, Pistas, Encuentro).
 *
 * Tabla `etapa`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 * Los números de cantidad son datos para sugerir el pase: el Service los evalúa y avisa, nunca bloquea.
 */
final class Etapa
{
    public function __construct(
        public ?int    $id,               // null mientras no se guardó en la base
        public int     $idRama,
        public int     $numero,           // Orden dentro de la rama
        public string  $titulo,
        public ?string $habilitaciones,   // Texto informativo, no bloquea nada
        public ?int    $minCompetencias,  // LyL y Scouts: mínimo sugerido
        public ?int    $maxCompetencias,  // LyL y Scouts: máximo sugerido
        public ?int    $pctDesde,         // Caminantes: % de avance desde
        public ?int    $pctHasta,         // Caminantes: % de avance hasta
    ) {}

    /**
     * Arma el objeto desde una fila de `etapa`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idRama: (int) $fila['id_rama'],
            numero: (int) $fila['numero'],
            titulo: (string) $fila['titulo'],
            habilitaciones: $fila['habilitaciones'] === null ? null : (string) $fila['habilitaciones'],
            minCompetencias: $fila['min_competencias'] === null ? null : (int) $fila['min_competencias'],
            maxCompetencias: $fila['max_competencias'] === null ? null : (int) $fila['max_competencias'],
            pctDesde: $fila['pct_desde'] === null ? null : (int) $fila['pct_desde'],
            pctHasta: $fila['pct_hasta'] === null ? null : (int) $fila['pct_hasta'],
        );
    }
}
