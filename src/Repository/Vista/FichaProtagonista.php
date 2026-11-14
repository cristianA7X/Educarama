<?php
// src/Repository/Vista/FichaProtagonista.php

declare(strict_types=1);

namespace Educarama\Repository\Vista;

use Educarama\Model\Persona;
use Educarama\Model\Protagonista;

/**
 * Un protagonista con sus datos personales y el nombre de su patrulla, para
 * listados, cards y perfil. Sale de un JOIN protagonista + persona + patrulla
 * (ProtagonistaRepository::listarFichasPorSeccion y compañía), así un listado
 * se resuelve en una sola consulta.
 *
 * Es una clase "vista": no es una tabla, y no se guarda. Para modificar se usan
 * los Models que trae adentro.
 */
final class FichaProtagonista
{
    public function __construct(
        public Protagonista $protagonista,
        public Persona      $persona,
        public ?string      $nombrePatrulla,  // null si no tiene patrulla
    ) {}

    /** "Apellido, Nombre", como se ordenan los listados. */
    public function nombreCompleto(): string
    {
        return $this->persona->apellido . ', ' . $this->persona->nombre;
    }

    /**
     * Arma la ficha desde una fila del JOIN. La consulta trae p.* (columnas de
     * protagonista) más las de persona y patrulla con alias, porque `id` y
     * `nombre` se repiten entre tablas.
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            protagonista: Protagonista::desdeFila($fila),
            persona: Persona::desdeFila([
                'id'               => $fila['id_persona'],
                'dni'              => $fila['persona_dni'],
                'nombre'           => $fila['persona_nombre'],
                'apellido'         => $fila['persona_apellido'],
                'fecha_nacimiento' => $fila['persona_fecha_nacimiento'],
            ]),
            nombrePatrulla: $fila['patrulla_nombre'] === null ? null : (string) $fila['patrulla_nombre'],
        );
    }
}
