<?php
// src/Service/Vista/PantallaCompetencias.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/**
 * Todo lo que necesita la pantalla de competencias de un protagonista
 * (reglas del proyecto, "Pantalla de competencias del protagonista"). La arma
 * ProgresionService::pantallaCompetencias().
 */
final class PantallaCompetencias
{
    /**
     * @param AreaEnPantalla[]      $areas    Una por pestaña, en el orden de la rama.
     * @param array<int, string>    $nombresEtapas Id => título de cada etapa de la rama ("Pata Tierna"),
     *                                        para mostrar en qué etapa se eligió cada competencia.
     * @param array<string, string> $terminos Términos de la rama por clave (nomenclatura):
     *                                        'areas' => 'Senderos', 'competencias' => '...',
     *                                        'pequenio_equipo' (no está en Rovers).
     */
    public function __construct(
        public readonly ResumenProgresion $resumen,      // Contadores, adeudado, insignia pendiente
        public readonly array             $areas,
        public readonly array             $nombresEtapas,
        public readonly array             $terminos,
        public readonly bool              $puedeEditar,  // Responsable en adelante: avanzar, tildar y "Corregir"
        public readonly bool              $puedeQuitar,  // Jefe: quitar etapas logradas e insignias
    ) {}
}
