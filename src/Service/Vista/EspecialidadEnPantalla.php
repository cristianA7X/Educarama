<?php
// src/Service/Vista/EspecialidadEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Enum\EstadoProgresion;
use Educarama\Model\Especialidad;

/** Una especialidad del catálogo con su estado para el protagonista (sin fila = pendiente). */
final class EspecialidadEnPantalla
{
    public function __construct(
        public readonly Especialidad      $especialidad,
        public readonly ?EstadoProgresion $estado,  // null = pendiente
        public readonly ?string           $fecha,
    ) {}
}
