<?php
// src/Service/Vista/EventoEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/** Un evento del listado con los botones que el usuario puede usar sobre él. */
final class EventoEnPantalla
{
    public function __construct(
        public readonly EventoConSecciones $evento,
        public readonly bool               $puedeEditar,    // Editar, desactivar y reactivar (Responsable donde corresponde)
        public readonly bool               $puedeEliminar,  // Jefe donde corresponde
    ) {}
}
