<?php
// src/Repository/Vista/ProtagonistaEnNomina.php

declare(strict_types=1);

namespace Educarama\Repository\Vista;

/**
 * Fila de la nómina general de protagonistas: datos personales, sección, grupo,
 * rama, patrulla y resumen de progresión (etapa actual y competencias). Sale de
 * una sola consulta con JOINs y subqueries (ProtagonistaRepository::listarNomina).
 *
 * Es una clase plana: todos los campos necesarios para la tabla sin consultas
 * adicionales.
 */
final class ProtagonistaEnNomina
{
    public function __construct(
        public int     $idProtagonista,
        public int     $dni,
        public string  $nombre,
        public string  $apellido,
        public ?string $fechaNacimiento,
        public ?int    $anioIngreso,
        public bool    $promesa,
        public int     $idSeccion,
        public string  $seccionNombre,
        public int     $idGrupo,
        public string  $grupoCodigo,
        public string  $grupoNombre,
        public int     $idRama,
        public string  $ramaTitulo,
        public ?int    $idPatrulla,
        public ?string $nombrePatrulla,
        public ?string $etapaActual,
        public int     $competenciasDesarrolladas,
        public int     $competenciasTotal,
    ) {}

    /**
     * @param array<string, mixed> $fila Fila del JOIN con los alias de la consulta.
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idProtagonista:             (int) $fila['id'],
            dni:                        (int) $fila['persona_dni'],
            nombre:                     (string) $fila['persona_nombre'],
            apellido:                   (string) $fila['persona_apellido'],
            fechaNacimiento:            $fila['persona_fecha_nacimiento'] === null ? null : (string) $fila['persona_fecha_nacimiento'],
            anioIngreso:                $fila['anio_ingreso'] === null ? null : (int) $fila['anio_ingreso'],
            promesa:                    (bool) $fila['promesa'],
            idSeccion:                  (int) $fila['id_seccion'],
            seccionNombre:              (string) $fila['seccion_nombre'],
            idGrupo:                    (int) $fila['grupo_id'],
            grupoCodigo:                (string) $fila['grupo_codigo'],
            grupoNombre:                (string) $fila['grupo_nombre'],
            idRama:                     (int) $fila['rama_id'],
            ramaTitulo:                 (string) $fila['rama_titulo'],
            idPatrulla:                 $fila['id_patrulla'] === null ? null : (int) $fila['id_patrulla'],
            nombrePatrulla:             $fila['patrulla_nombre'] === null ? null : (string) $fila['patrulla_nombre'],
            etapaActual:                $fila['etapa_actual_titulo'] === null ? null : (string) $fila['etapa_actual_titulo'],
            competenciasDesarrolladas:  (int) $fila['competencias_desarrolladas'],
            competenciasTotal:          (int) $fila['competencias_total'],
        );
    }
}
