<?php
// src/Service/Vista/ResultadoProgresion.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/**
 * Lo que devuelve una operación de progresión que ya se guardó.
 * Puede traer sugerencias ("¿dar la competencia por desarrollada?"), que no
 * frenan nada: la vista las muestra y el educador decide si hace otra operación.
 *
 * Tiene solo propiedades públicas, así el Controller puede responder el fetch
 * con json_encode($resultado) directamente.
 */
final class ResultadoProgresion
{
    /** @param Aviso[] $sugerencias */
    public function __construct(
        public array $sugerencias = [],
    ) {}
}
