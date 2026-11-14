<?php
// src/Model/Etapa.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Etapa de progresión de una rama (ej. Pata Tierna, Pistas, Encuentro).
 *
 * Tabla `etapa`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 * El cupo de competencias es un dato para avisar y sugerir el pase: el Service lo evalúa, nunca bloquea.
 */
final class Etapa
{
    public function __construct(
        public ?int    $id,                 // null mientras no se guardó en la base
        public int     $idRama,
        public int     $numero,             // Orden dentro de la rama
        public string  $titulo,
        public ?string $habilitaciones,     // Texto informativo, no bloquea nada
        public int     $competenciasEtapa,  // Cupo fijo; los cupos de una rama suman su total de competencias
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
            competenciasEtapa: (int) $fila['competencias_etapa'],
        );
    }
}
