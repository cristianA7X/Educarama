<?php
// src/Service/Vista/AccesoEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Enum\NivelRol;
use Educarama\Repository\Vista\UsuarioConAcceso;

/**
 * Una fila de la lista de accesos, con lo que el usuario puede hacer sobre
 * ella. La vista no repite las reglas: muestra el select y el botón solo si
 * hay algo para elegir.
 */
final class AccesoEnPantalla
{
    /** @param NivelRol[] $rolesAsignables Los que el select ofrece; vacío = no puede cambiarle el rol. */
    public function __construct(
        public readonly UsuarioConAcceso $usuario,
        public readonly array            $rolesAsignables,
        public readonly bool             $puedeQuitar,
    ) {}

    public function puedeCambiarRol(): bool
    {
        return $this->rolesAsignables !== [];
    }
}
