<?php
// src/Model/Enum/Prioridad.php

declare(strict_types=1);

namespace Educarama\Model\Enum;

/**
 * Prioridad de un evento.
 *
 * Valores guardados en: evento.prioridad.
 *
 * Un enum es una lista cerrada de valores: PHP no deja usar un valor que no esté acá.
 * Pasar del texto de la base al enum:  Prioridad::from($fila['...'])
 * Pasar del enum al texto de la base:  $valor->value
 */
enum Prioridad: string
{
    case Alta = 'alta';
    case Media = 'media';
    case Baja = 'baja';

    /** Texto para mostrar en pantalla. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Alta => 'Alta',
            self::Media => 'Media',
            self::Baja => 'Baja',
        };
    }
}
