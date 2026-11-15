<?php
// src/Service/Vista/TareaEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\TareaEtapa;

/** Una tarea de etapa con su casilla: cumplida (con fecha) o no. */
final class TareaEnPantalla
{
    public function __construct(
        public readonly TareaEtapa $tarea,
        public readonly ?string    $fechaCumplida,  // null = sin cumplir
    ) {}

    public function cumplida(): bool
    {
        return $this->fechaCumplida !== null;
    }
}
