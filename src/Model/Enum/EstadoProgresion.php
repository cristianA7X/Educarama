<?php
// src/Model/Enum/EstadoProgresion.php

declare(strict_types=1);

namespace Educarama\Model\Enum;

/**
 * Estado de una competencia o especialidad en curso.
 *
 * "Pendiente" no existe acá a propósito: lo pendiente no tiene fila en la base
 * (ver tablas de progresión).
 *
 * Valores guardados en: competencia_protagonista.estado y especialidad_protagonista.estado.
 *
 * Un enum es una lista cerrada de valores: PHP no deja usar un valor que no esté acá.
 * Pasar del texto de la base al enum:  EstadoProgresion::from($fila['...'])
 * Pasar del enum al texto de la base:  $valor->value
 */
enum EstadoProgresion: string
{
    case EnProceso = 'en_proceso';
    case Desarrollada = 'desarrollada';

    /** Texto para mostrar en pantalla. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::EnProceso => 'En proceso',
            self::Desarrollada => 'Desarrollada',
        };
    }
}
