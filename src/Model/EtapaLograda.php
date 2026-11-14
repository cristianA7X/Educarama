<?php
// src/Model/EtapaLograda.php

declare(strict_types=1);

namespace Educarama\Model;

use Educarama\Model\Enum\Elemento;

/**
 * Etapa que un protagonista logró. La etapa actual es la última lograda.
 *
 * Tabla `etapa_lograda`. Tabla de progresión.
 * Tabla de progresión: si no hay fila, está pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: la identifican id_protagonista + id_etapa.
 */
final class EtapaLograda
{
    public function __construct(
        public int       $idProtagonista,
        public int       $idEtapa,
        public ?Elemento $elemento,        // Solo Caminantes
        public string    $fecha,
    ) {}

    /**
     * Arma el objeto desde una fila de `etapa_lograda`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista: (int) $fila['id_protagonista'],
            idEtapa: (int) $fila['id_etapa'],
            elemento: $fila['elemento'] === null ? null : Elemento::from($fila['elemento']),
            fecha: (string) $fila['fecha'],
        );
    }
}
