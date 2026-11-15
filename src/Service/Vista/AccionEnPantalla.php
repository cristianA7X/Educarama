<?php
// src/Service/Vista/AccionEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Accion;

/** Una acción de la pantalla de competencias: la casilla sí/no. */
final class AccionEnPantalla
{
    public function __construct(
        public readonly Accion $accion,   // esOpcional: solo cuenta en Lobatos y Lobeznas
        public readonly bool   $cumplida,
    ) {}
}
