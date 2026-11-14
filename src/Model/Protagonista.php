<?php
// src/Model/Protagonista.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Chico, chica o joven de una sección. No es un usuario del sistema.
 *
 * Tabla `protagonista`. Tabla dinámica: la cargan los usuarios.
 */
final class Protagonista
{
    public function __construct(
        public ?int $id,           // null mientras no se guardó en la base
        public int  $idPersona,    // Sus datos personales
        public int  $idSeccion,
        public ?int $idPatrulla,   // Debe ser de la misma sección (lo valida el Service)
        public bool $promesa,      // Si ya hizo la Promesa / el Compromiso
        public ?int $anioIngreso,
    ) {}

    /**
     * Arma el objeto desde una fila de `protagonista`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idPersona: (int) $fila['id_persona'],
            idSeccion: (int) $fila['id_seccion'],
            idPatrulla: $fila['id_patrulla'] === null ? null : (int) $fila['id_patrulla'],
            promesa: (bool) $fila['promesa'],
            anioIngreso: $fila['anio_ingreso'] === null ? null : (int) $fila['anio_ingreso'],
        );
    }
}
