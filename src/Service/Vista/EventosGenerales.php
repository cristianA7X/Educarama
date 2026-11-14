<?php
// src/Service/Vista/EventosGenerales.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/**
 * Todos los eventos que el usuario puede ver, agrupados por grupo. Para la
 * pantalla general de eventos (sin parámetros) y la de eventos grupales.
 */
final class EventosGenerales
{
    /**
     * @param list<array{grupo: GrupoEnPantalla, pantalla: EventosEnPantalla}> $porGrupo
     *        Un elemento por cada grupo donde el usuario tiene acceso, ordenados
     *        por código de grupo. Cada `pantalla` trae los eventos, los nombres
     *        de secciones del grupo y los permisos del usuario.
     */
    public function __construct(
        public readonly array $porGrupo,
    ) {}

    /** Cantidad total de eventos en todos los grupos. */
    public function total(): int
    {
        $n = 0;
        foreach ($this->porGrupo as $fila) {
            $n += count($fila['pantalla']->eventos);
        }
        return $n;
    }
}
