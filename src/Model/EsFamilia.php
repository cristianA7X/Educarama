<?php
// src/Model/EsFamilia.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Vínculo entre un protagonista y un familiar.
 *
 * Tabla `es_familia`. Tabla dinámica: la cargan los usuarios.
 * Sin id propio: la identifican id_protagonista + id_familiar.
 */
final class EsFamilia
{
    public function __construct(
        public int     $idProtagonista,
        public int     $idFamiliar,
        public ?string $vinculo,                 // Ej. 'Madre', 'Tío'
        public bool    $esContactoDeEmergencia,
    ) {}

    /**
     * Arma el objeto desde una fila de `es_familia`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista: (int) $fila['id_protagonista'],
            idFamiliar: (int) $fila['id_familiar'],
            vinculo: $fila['vinculo'] === null ? null : (string) $fila['vinculo'],
            esContactoDeEmergencia: (bool) $fila['es_contacto_de_emergencia'],
        );
    }
}
