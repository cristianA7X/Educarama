<?php
// src/Model/Enum/Elemento.php

declare(strict_types=1);

namespace Educarama\Model\Enum;

/**
 * Elemento que eligió un/a Caminante al lograr una etapa
 * (los Caminantes eligen el orden en que los reciben).
 *
 * Valores guardados en: etapa_lograda.elemento.
 *
 * Un enum es una lista cerrada de valores: PHP no deja usar un valor que no esté acá.
 * Pasar del texto de la base al enum:  Elemento::from($fila['...'])
 * Pasar del enum al texto de la base:  $valor->value
 */
enum Elemento: string
{
    case Agua = 'agua';
    case Tierra = 'tierra';
    case Fuego = 'fuego';
    case Aire = 'aire';

    /** Texto para mostrar en pantalla. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Agua => 'Agua',
            self::Tierra => 'Tierra',
            self::Fuego => 'Fuego',
            self::Aire => 'Aire',
        };
    }
}
