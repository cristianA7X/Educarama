<?php
// src/Service/Vista/GrupoEnPantalla.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Grupo;

/** Un grupo con su rol para el usuario y las secciones que puede ver (inicio y pantalla del grupo). */
final class GrupoEnPantalla
{
    /** @param SeccionEnPantalla[] $secciones */
    public function __construct(
        public readonly Grupo     $grupo,
        public readonly ?NivelRol $nivel,      // Rol de grupo; null si entra solo por alguna sección
        public readonly array     $secciones,
    ) {}

    /** Si puede ver la pantalla de eventos del grupo (hace falta un rol de grupo). */
    public function tieneRolDeGrupo(): bool
    {
        return $this->nivel !== null;
    }
}
