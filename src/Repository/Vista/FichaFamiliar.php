<?php
// src/Repository/Vista/FichaFamiliar.php

declare(strict_types=1);

namespace Educarama\Repository\Vista;

use Educarama\Model\EsFamilia;
use Educarama\Model\Familiar;
use Educarama\Model\Persona;

/**
 * Un familiar visto desde un protagonista: sus datos de contacto, sus datos
 * personales y el vínculo con ESE protagonista (madre, tío, contacto de emergencia).
 * Sale de un JOIN familiar + persona + es_familia (FamiliarRepository::listarFichasPorProtagonista).
 *
 * Clase "vista": no es una tabla. Para modificar se usan los Models que trae adentro.
 */
final class FichaFamiliar
{
    public function __construct(
        public Familiar  $familiar,
        public Persona   $persona,
        public EsFamilia $vinculo,
    ) {}

    /** "Apellido, Nombre". */
    public function nombreCompleto(): string
    {
        return $this->persona->apellido . ', ' . $this->persona->nombre;
    }

    /**
     * Arma la ficha desde una fila del JOIN. La consulta trae f.* (columnas de
     * familiar) más las de persona y es_familia con alias, porque `id` se repite.
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            familiar: Familiar::desdeFila($fila),
            persona: Persona::desdeFila([
                'id'               => $fila['id_persona'],
                'dni'              => $fila['persona_dni'],
                'nombre'           => $fila['persona_nombre'],
                'apellido'         => $fila['persona_apellido'],
                'fecha_nacimiento' => $fila['persona_fecha_nacimiento'],
            ]),
            vinculo: EsFamilia::desdeFila([
                'id_protagonista'           => $fila['ef_id_protagonista'],
                'id_familiar'               => $fila['id'],
                'vinculo'                   => $fila['ef_vinculo'],
                'es_contacto_de_emergencia' => $fila['ef_es_contacto_de_emergencia'],
            ]),
        );
    }
}
