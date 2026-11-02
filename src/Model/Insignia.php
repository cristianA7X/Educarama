<?php
// src/Model/Insignia.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Reconocimiento que se entrega a un protagonista: de etapa, especial o de partida.
 *
 * Tabla `insignia`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 */
final class Insignia
{
    public function __construct(
        public ?int    $id,       // null mientras no se guardó en la base
        public string  $titulo,
        public ?string $imagen,   // Ruta relativa a views/assets/images/
        public ?int    $idEtapa,  // Si es la insignia de una etapa
        public ?int    $idRama,   // Si pertenece a una rama
    ) {}

    /**
     * Arma el objeto desde una fila de `insignia`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            titulo: (string) $fila['titulo'],
            imagen: $fila['imagen'] === null ? null : (string) $fila['imagen'],
            idEtapa: $fila['id_etapa'] === null ? null : (int) $fila['id_etapa'],
            idRama: $fila['id_rama'] === null ? null : (int) $fila['id_rama'],
        );
    }
}
