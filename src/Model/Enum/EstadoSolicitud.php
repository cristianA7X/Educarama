<?php
// src/Model/Enum/EstadoSolicitud.php

declare(strict_types=1);

namespace Educarama\Model\Enum;

/**
 * Estado de una solicitud de alta de grupo, que revisa un dev.
 *
 * Valores guardados en: solicitud_alta.estado.
 *
 * Un enum es una lista cerrada de valores: PHP no deja usar un valor que no esté acá.
 * Pasar del texto de la base al enum:  EstadoSolicitud::from($fila['...'])
 * Pasar del enum al texto de la base:  $valor->value
 */
enum EstadoSolicitud: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';

    /** Texto para mostrar en pantalla. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
        };
    }
}
