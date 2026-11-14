<?php
// src/Service/Vista/OpcionesEvento.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Grupo;
use Educarama\Model\Seccion;

/**
 * Lo que puede elegir el usuario en el formulario de un evento: solo las
 * secciones donde es Responsable o más, y "todo el grupo" solo si es
 * Responsable de grupo (o Jefe de grupo).
 */
final class OpcionesEvento
{
    /** @param Seccion[] $secciones Las del grupo en las que puede crear o editar eventos. */
    public function __construct(
        public readonly Grupo $grupo,
        public readonly array $secciones,
        public readonly bool  $puedeTodoElGrupo,
    ) {}

    public function hayAlgunaOpcion(): bool
    {
        return $this->puedeTodoElGrupo || $this->secciones !== [];
    }
}
