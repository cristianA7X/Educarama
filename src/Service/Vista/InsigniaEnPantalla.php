<?php
// src/Service/Vista/InsigniaEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Insignia;

/** Una insignia que puede recibir el protagonista, entregada (con su fecha) o no. */
final class InsigniaEnPantalla
{
    public function __construct(
        public readonly Insignia $insignia,
        public readonly ?string  $fechaOtorgada,  // null = todavía no la recibió
    ) {}

    public function otorgada(): bool
    {
        return $this->fechaOtorgada !== null;
    }
}
