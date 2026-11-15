<?php
// src/Service/Vista/AreaEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/** Una pestaña de la pantalla de competencias: el área con su contador y sus competencias. */
final class AreaEnPantalla
{
    /** @param CompetenciaEnPantalla[] $competencias Todas las del área en la rama, en orden. */
    public function __construct(
        public readonly ContadorArea $contador,   // idArea, título en la rama, desarrolladas / total
        public readonly array        $competencias,
    ) {}
}
