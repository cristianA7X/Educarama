<?php
// src/Service/Vista/ConteoSeccion.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Patrulla;

/**
 * Números de una sección para su perfil. Sin datos personales: lo puede ver
 * también un Supervisor.
 */
final class ConteoSeccion
{
    /** @param list<array{patrulla: Patrulla, cantidad: int}> $porPatrulla En el orden de las patrullas (por nombre). */
    public function __construct(
        public readonly int   $protagonistas,
        public readonly array $porPatrulla,
        public readonly int   $sinPatrulla,
    ) {}
}
