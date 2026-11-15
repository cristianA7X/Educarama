<?php
// src/Service/Vista/EventosEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/**
 * El listado de eventos de un grupo o de una sección, con lo que necesita la
 * pantalla para mostrar nombres de secciones y botones. La arma EventoService.
 */
final class EventosEnPantalla
{
    /**
     * @param EventoEnPantalla[]  $eventos          Por fecha de inicio.
     * @param array<int, string>  $nombresSecciones Id => nombre de cada sección del grupo
     *                                              (para mostrar "Manada Luna Llena, Unidad...").
     */
    public function __construct(
        public readonly array $eventos,
        public readonly array $nombresSecciones,
        public readonly bool  $puedeCrear,         // Hay al menos una opción en el formulario
        public readonly bool  $puedeVerInactivos,  // Responsable en adelante
        public readonly bool  $incluyeInactivos,   // Si este listado los trae
    ) {}
}
