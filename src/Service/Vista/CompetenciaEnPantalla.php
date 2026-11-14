<?php
// src/Service/Vista/CompetenciaEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Competencia;
use Educarama\Model\Enum\EstadoProgresion;

/**
 * Una competencia de la rama tal como la ve la pantalla, la haya elegido o no
 * el protagonista (sin fila = pendiente).
 */
final class CompetenciaEnPantalla
{
    /** @param AccionEnPantalla[] $acciones Vacío en Rovers (no usa acciones). */
    public function __construct(
        public readonly Competencia       $competencia,
        public readonly ?EstadoProgresion $estado,    // null = pendiente
        public readonly ?int              $idEtapa,   // Etapa en que la eligió; null si está pendiente
        public readonly array             $acciones,
    ) {}
}
