<?php
// src/Service/Vista/ContadorArea.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/**
 * Cuántas competencias desarrolló un protagonista en un área, sobre el total
 * del área en su rama. Es el contador de cada pestaña de la pantalla de competencias.
 */
final class ContadorArea
{
    public function __construct(
        public int    $idArea,
        public string $titulo,         // Nombre del área en esa rama (area_rama)
        public int    $desarrolladas,
        public int    $total,
    ) {}
}
