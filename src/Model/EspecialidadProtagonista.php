<?php
// src/Model/EspecialidadProtagonista.php

declare(strict_types=1);

namespace Educarama\Model;

use Educarama\Model\Enum\EstadoProgresion;

/**
 * Especialidad que un protagonista está haciendo o ya terminó.
 *
 * Tabla `especialidad_protagonista`. Tabla de progresión.
 * Tabla de progresión: si no hay fila, está pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: la identifican id_protagonista + id_especialidad.
 */
final class EspecialidadProtagonista
{
    public function __construct(
        public int              $idProtagonista,
        public int              $idEspecialidad,
        public EstadoProgresion $estado,
        public string           $fecha,
    ) {}

    /**
     * Arma el objeto desde una fila de `especialidad_protagonista`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista: (int) $fila['id_protagonista'],
            idEspecialidad: (int) $fila['id_especialidad'],
            estado: EstadoProgresion::from($fila['estado']),
            fecha: (string) $fila['fecha'],
        );
    }
}
