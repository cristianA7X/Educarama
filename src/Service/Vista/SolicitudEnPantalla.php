<?php
// src/Service/Vista/SolicitudEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\SolicitudAlta;

/**
 * Una solicitud de alta lista para mostrar: con el nombre de la rama de cada
 * sección y, para el admin, quién la envió.
 */
final class SolicitudEnPantalla
{
    /**
     * @param list<array{nombre: string, rama: string}> $secciones La rama con su nombre oficial ("Lobatos y Lobeznas").
     */
    public function __construct(
        public readonly SolicitudAlta $solicitud,
        public readonly ?string       $nombreSolicitante,  // Solo cuando la ve un admin
        public readonly ?string       $emailSolicitante,
        public readonly array         $secciones,
    ) {}
}
