<?php
// src/Service/Vista/EventoConSecciones.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Evento;

/**
 * Un evento junto con los ids de sus secciones, para listados y para el
 * formulario de edición. Lista vacía = evento de todo el grupo.
 */
final class EventoConSecciones
{
    /** @param int[] $idSecciones */
    public function __construct(
        public readonly Evento $evento,
        public readonly array  $idSecciones,
    ) {}

    public function esDeTodoElGrupo(): bool
    {
        return $this->idSecciones === [];
    }
}
