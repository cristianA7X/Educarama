<?php
// src/Model/TareaEtapaCumplida.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Tarea de etapa que un protagonista cumplió.
 *
 * Tabla `tarea_etapa_cumplida`. Tabla de progresión.
 * Tabla de progresión: si no hay fila, está pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: la identifican id_protagonista + id_tarea_etapa.
 */
final class TareaEtapaCumplida
{
    public function __construct(
        public int    $idProtagonista,
        public int    $idTareaEtapa,
        public string $fecha,
    ) {}

    /**
     * Arma el objeto desde una fila de `tarea_etapa_cumplida`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista: (int) $fila['id_protagonista'],
            idTareaEtapa: (int) $fila['id_tarea_etapa'],
            fecha: (string) $fila['fecha'],
        );
    }
}
