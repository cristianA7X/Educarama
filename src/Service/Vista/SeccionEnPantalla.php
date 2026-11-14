<?php
// src/Service/Vista/SeccionEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Grupo;
use Educarama\Model\Rama;
use Educarama\Model\Seccion;

/**
 * Una sección con lo que necesita el encabezado de sus páginas: su rama (para
 * la clase CSS y los nombres), su grupo, los términos de la rama y qué puede
 * hacer el usuario. La arma SeccionService.
 */
final class SeccionEnPantalla
{
    /**
     * @param array<string, string> $terminos Términos de la rama por clave (nomenclatura):
     *                                        'areas', 'competencias', 'pequenio_equipo' (no está en Rovers).
     */
    public function __construct(
        public readonly Seccion   $seccion,
        public readonly Rama      $rama,
        public readonly Grupo     $grupo,
        public readonly array     $terminos,
        public readonly ?NivelRol $nivel,   // Rol del usuario en la sección; null = sin acceso a la sección (ve el grupo)
    ) {}

    /** Ayudante en adelante: puede entrar a la sección y ver a sus protagonistas. */
    public function puedeVerDetalle(): bool
    {
        return $this->alcanza(NivelRol::Ayudante);
    }

    /** Responsable en adelante: altas, ediciones, patrullas. */
    public function puedeEditar(): bool
    {
        return $this->alcanza(NivelRol::Responsable);
    }

    /** Jefe: eliminar protagonistas y patrullas. */
    public function puedeEliminar(): bool
    {
        return $this->alcanza(NivelRol::Jefe);
    }

    /** "Seisena", "Patrulla" o "Equipo"; null en Rovers (no tiene pequeño equipo). */
    public function pequenioEquipo(): ?string
    {
        return $this->terminos['pequenio_equipo'] ?? null;
    }

    private function alcanza(NivelRol $minimo): bool
    {
        return $this->nivel !== null && $this->nivel->value >= $minimo->value;
    }
}
