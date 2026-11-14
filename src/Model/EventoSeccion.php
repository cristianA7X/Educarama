<?php
// src/Model/EventoSeccion.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Sección a la que pertenece un evento.
 *
 * Tabla `evento_seccion`. Tabla dinámica: la cargan los usuarios.
 * Sin id propio: la identifican id_evento + id_seccion.
 */
final class EventoSeccion
{
    public function __construct(
        public int $idEvento,
        public int $idSeccion,
    ) {}

    /**
     * Arma el objeto desde una fila de `evento_seccion`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idEvento: (int) $fila['id_evento'],
            idSeccion: (int) $fila['id_seccion'],
        );
    }
}
