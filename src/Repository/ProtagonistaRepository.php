<?php
// src/Repository/ProtagonistaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Protagonista;

/**
 * Lectura y escritura de `protagonista`.
 *
 * Devuelve solo el Model Protagonista: los datos personales (nombre, DNI...)
 * están en `persona` y se piden a PersonaRepository con $protagonista->idPersona.
 *
 * No hay "listar todos": qué secciones puede ver cada usuario lo decide el
 * Service según sus accesos; acá solo se filtra por la sección o patrulla pedida.
 *
 * Reglas que valida el Service, no este repositorio: que la patrulla sea de la
 * misma sección, y quién puede crear, modificar o eliminar.
 */
final class ProtagonistaRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Protagonista
    {
        return $this->buscarUno('SELECT * FROM protagonista WHERE id = ?', [$id], Protagonista::class);
    }

    /**
     * Los protagonistas de una sección, ordenados por apellido y nombre.
     *
     * @return Protagonista[]
     */
    public function listarPorSeccion(int $idSeccion): array
    {
        // El JOIN con persona es solo para ordenar: se devuelven únicamente las columnas de protagonista.
        return $this->listar(
            'SELECT p.*
               FROM protagonista p
               JOIN persona per ON per.id = p.id_persona
              WHERE p.id_seccion = ?
              ORDER BY per.apellido, per.nombre',
            [$idSeccion],
            Protagonista::class,
        );
    }

    /**
     * Los integrantes de una patrulla, ordenados por apellido y nombre.
     *
     * @return Protagonista[]
     */
    public function listarPorPatrulla(int $idPatrulla): array
    {
        return $this->listar(
            'SELECT p.*
               FROM protagonista p
               JOIN persona per ON per.id = p.id_persona
              WHERE p.id_patrulla = ?
              ORDER BY per.apellido, per.nombre',
            [$idPatrulla],
            Protagonista::class,
        );
    }

    /**
     * Guarda un protagonista nuevo, le completa el id y lo devuelve.
     * La persona tiene que existir antes (PersonaRepository::insertar); si el
     * alta crea las dos, el Service lo hace dentro de una transacción.
     */
    public function insertar(Protagonista $protagonista): int
    {
        $protagonista->id = $this->insertarFila(
            'INSERT INTO protagonista (id_persona, id_seccion, id_patrulla, promesa, anio_ingreso)
             VALUES (:id_persona, :id_seccion, :id_patrulla, :promesa, :anio_ingreso)',
            $this->parametros($protagonista),
        );

        return $protagonista->id;
    }

    public function actualizar(Protagonista $protagonista): void
    {
        $this->ejecutar(
            'UPDATE protagonista
                SET id_persona = :id_persona, id_seccion = :id_seccion, id_patrulla = :id_patrulla,
                    promesa = :promesa, anio_ingreso = :anio_ingreso
              WHERE id = :id',
            $this->parametros($protagonista) + ['id' => $this->exigirId($protagonista->id)],
        );
    }

    /**
     * Borra el protagonista y, en cascada (lo hace la base), toda su progresión,
     * sus documentos y sus vínculos con familiares.
     * Su fila en `persona` NO se borra: puede ser además familiar de otro protagonista.
     */
    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM protagonista WHERE id = ?', [$id]);
    }

    /**
     * Traduce el Model a los parámetros del INSERT/UPDATE (lo inverso de desdeFila).
     *
     * @return array<string, mixed>
     */
    private function parametros(Protagonista $protagonista): array
    {
        return [
            'id_persona'   => $protagonista->idPersona,
            'id_seccion'   => $protagonista->idSeccion,
            'id_patrulla'  => $protagonista->idPatrulla,
            'promesa'      => (int) $protagonista->promesa,  // bool → TINYINT
            'anio_ingreso' => $protagonista->anioIngreso,
        ];
    }
}
