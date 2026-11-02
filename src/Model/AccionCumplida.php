<?php
// src/Model/AccionCumplida.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Acción que un protagonista cumplió.
 *
 * Tabla `accion_cumplida`. Tabla de progresión.
 * Tabla de progresión: si no hay fila, está pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: la identifican id_protagonista + id_accion.
 */
final class AccionCumplida
{
    public function __construct(
        public int    $idProtagonista,
        public int    $idAccion,
        public string $fecha,
    ) {}

    /**
     * Arma el objeto desde una fila de `accion_cumplida`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista: (int) $fila['id_protagonista'],
            idAccion: (int) $fila['id_accion'],
            fecha: (string) $fila['fecha'],
        );
    }
}
