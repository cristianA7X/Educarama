<?php
// src/Model/CompetenciaProtagonista.php

declare(strict_types=1);

namespace Educarama\Model;

use Educarama\Model\Enum\EstadoProgresion;

/**
 * Competencia que un protagonista está desarrollando o ya desarrolló.
 *
 * Tabla `competencia_protagonista`. Tabla de progresión.
 * Tabla de progresión: si no hay fila, está pendiente. La fila la crea un educador al confirmar.
 * Pasar de 'en proceso' a 'desarrollada' es un UPDATE de esta fila.
 * Sin id propio: la identifican id_protagonista + id_competencia.
 */
final class CompetenciaProtagonista
{
    public function __construct(
        public int              $idProtagonista,
        public int              $idCompetencia,
        public ?int             $idEtapa,         // Etapa en la que la eligió
        public EstadoProgresion $estado,
        public string           $fecha,           // Último cambio de estado
    ) {}

    /**
     * Arma el objeto desde una fila de `competencia_protagonista`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista: (int) $fila['id_protagonista'],
            idCompetencia: (int) $fila['id_competencia'],
            idEtapa: $fila['id_etapa'] === null ? null : (int) $fila['id_etapa'],
            estado: EstadoProgresion::from($fila['estado']),
            fecha: (string) $fila['fecha'],
        );
    }
}
