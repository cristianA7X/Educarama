<?php
// src/Model/Enum/NivelRol.php

declare(strict_types=1);

namespace Educarama\Model\Enum;

/**
 * Nivel de cada rol, igual a la columna rol.nivel.
 *
 * Sirve para comparar permisos sin escribir números sueltos en el código:
 *   if ($nivel >= NivelRol::Responsable->value) { ... puede editar ... }
 *
 * Valores guardados en: rol.nivel.
 *
 * Un enum es una lista cerrada de valores: PHP no deja usar un valor que no esté acá.
 * Pasar del texto de la base al enum:  NivelRol::from($fila['...'])
 * Pasar del enum al texto de la base:  $valor->value
 */
enum NivelRol: int
{
    case Supervisor = 1;
    case Ayudante = 2;
    case Responsable = 3;
    case Jefe = 4;

    /** Texto para mostrar en pantalla. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Supervisor => 'Supervisor',
            self::Ayudante => 'Ayudante',
            self::Responsable => 'Responsable',
            self::Jefe => 'Jefe',
        };
    }
}
