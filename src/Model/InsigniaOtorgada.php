<?php
// src/Model/InsigniaOtorgada.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Insignia entregada a un protagonista (incluidas las de etapa).
 *
 * Tabla `insignia_otorgada`. Tabla de progresión.
 * Tabla de progresión: si no hay fila, está pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: la identifican id_protagonista + id_insignia.
 */
final class InsigniaOtorgada
{
    public function __construct(
        public int    $idProtagonista,
        public int    $idInsignia,
        public string $fecha,
    ) {}

    /**
     * Arma el objeto desde una fila de `insignia_otorgada`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista: (int) $fila['id_protagonista'],
            idInsignia: (int) $fila['id_insignia'],
            fecha: (string) $fila['fecha'],
        );
    }
}
