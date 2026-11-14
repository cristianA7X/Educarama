<?php
// src/Service/Vista/EtapaEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Etapa;
use Educarama\Model\EtapaLograda;

/** Una etapa de la rama en la pantalla "Etapas e insignias". */
final class EtapaEnPantalla
{
    /** @param TareaEnPantalla[] $tareas */
    public function __construct(
        public readonly Etapa         $etapa,
        public readonly ?EtapaLograda $lograda,     // null = no lograda (fecha y elemento si se logró)
        public readonly bool          $enCurso,     // La siguiente a la última lograda
        public readonly bool          $esLaActual,  // La última lograda: es la única que se puede quitar
        public readonly array         $tareas,
    ) {}
}
