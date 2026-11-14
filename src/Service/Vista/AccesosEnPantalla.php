<?php
// src/Service/Vista/AccesosEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Enum\NivelRol;

/** La lista de accesos de un grupo o de una sección, para la pantalla de gestión de roles. */
final class AccesosEnPantalla
{
    /**
     * @param AccesoEnPantalla[] $accesos         Primero los de mayor rol.
     * @param NivelRol[]         $rolesParaAgregar Los roles con que puede sumar a alguien; vacío = no puede agregar.
     */
    public function __construct(
        public readonly array $accesos,
        public readonly array $rolesParaAgregar,
    ) {}

    public function puedeAgregar(): bool
    {
        return $this->rolesParaAgregar !== [];
    }
}
