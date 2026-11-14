<?php
// src/Service/Vista/PantallaEtapas.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Area;

/**
 * La pantalla "Etapas e insignias" de un protagonista: las etapas de su rama
 * (logradas, en curso con sus tareas), las insignias que puede recibir y, en
 * las ramas que las usan, las especialidades. La arma ProgresionService::pantallaEtapas().
 */
final class PantallaEtapas
{
    /**
     * @param EtapaEnPantalla[]    $etapas         En orden.
     * @param InsigniaEnPantalla[] $insignias      Las de la rama y las generales.
     * @param list<array{area: Area, especialidades: EspecialidadEnPantalla[]}> $especialidades
     *        Por área. Vacío si la rama no usa especialidades.
     */
    public function __construct(
        public readonly ResumenProgresion $resumen,
        public readonly array             $etapas,
        public readonly array             $insignias,
        public readonly array             $especialidades,
        public readonly bool              $pideElemento, // Caminantes: al confirmar una etapa se elige el elemento
        public readonly bool              $puedeEditar,  // Responsable: tareas, confirmar etapa, otorgar, especialidades
        public readonly bool              $puedeQuitar,  // Jefe: quitar la última etapa y las insignias
    ) {}
}
