<?php
// src/Model/Rama.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Una de las 4 ramas del programa: Lobatos y Lobeznas, Scouts, Caminantes, Rovers.
 *
 * Tabla `rama`. Tabla estática: la cargan los devs; la aplicación solo la lee.
 * No confundir con Seccion: Rama es la categoría; Seccion es la de un grupo concreto.
 */
final class Rama
{
    public function __construct(
        public ?int   $id,                 // null mientras no se guardó en la base
        public string $titulo,             // Nombre oficial, ej. 'Lobatos y Lobeznas'
        public string $tituloCorto,        // Nombre común, ej. 'Manada'
        public bool   $usaEspecialidades,  // Solo LyL y Scouts
        public bool   $usaAcciones,        // Rovers mide competencias sin acciones
    ) {}

    /**
     * Arma el objeto desde una fila de `rama`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            titulo: (string) $fila['titulo'],
            tituloCorto: (string) $fila['titulo_corto'],
            usaEspecialidades: (bool) $fila['usa_especialidades'],
            usaAcciones: (bool) $fila['usa_acciones'],
        );
    }
}
