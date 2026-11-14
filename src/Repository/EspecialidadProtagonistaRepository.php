<?php
// src/Repository/EspecialidadProtagonistaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EspecialidadProtagonista;

/**
 * Lectura y escritura de `especialidad_protagonista`: las especialidades que un
 * protagonista está haciendo o ya terminó.
 *
 * Tabla de progresión: sin fila = pendiente. La fila la crea un educador al confirmar.
 * Sin id propio: cada fila se identifica por id_protagonista + id_especialidad.
 * Que solo las usen las ramas con rama.usa_especialidades lo valida el Service.
 */
final class EspecialidadProtagonistaRepository extends RepositorioBase
{
    public function buscar(int $idProtagonista, int $idEspecialidad): ?EspecialidadProtagonista
    {
        return $this->buscarUno(
            'SELECT * FROM especialidad_protagonista WHERE id_protagonista = ? AND id_especialidad = ?',
            [$idProtagonista, $idEspecialidad],
            EspecialidadProtagonista::class,
        );
    }

    /**
     * Todas las especialidades del protagonista, en el orden del catálogo (área y número).
     *
     * @return EspecialidadProtagonista[]
     */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        // El JOIN es solo para ordenar: se devuelven únicamente las columnas de especialidad_protagonista.
        return $this->listar(
            'SELECT ep.*
               FROM especialidad_protagonista ep
               JOIN especialidad e ON e.id = ep.id_especialidad
              WHERE ep.id_protagonista = ?
              ORDER BY e.id_area, e.numero',
            [$idProtagonista],
            EspecialidadProtagonista::class,
        );
    }

    /** Registra la especialidad, con el estado y la fecha que trae el objeto. */
    public function insertar(EspecialidadProtagonista $ep): void
    {
        $this->ejecutar(
            'INSERT INTO especialidad_protagonista (id_protagonista, id_especialidad, estado, fecha)
             VALUES (:id_protagonista, :id_especialidad, :estado, :fecha)',
            $this->parametros($ep),
        );
    }

    /** Cambia el estado (ej. en proceso → desarrollada) y la fecha del cambio. */
    public function actualizar(EspecialidadProtagonista $ep): void
    {
        $this->ejecutar(
            'UPDATE especialidad_protagonista
                SET estado = :estado, fecha = :fecha
              WHERE id_protagonista = :id_protagonista AND id_especialidad = :id_especialidad',
            $this->parametros($ep),
        );
    }

    /** Borra la fila: la especialidad vuelve a quedar pendiente. */
    public function eliminar(int $idProtagonista, int $idEspecialidad): void
    {
        $this->ejecutar(
            'DELETE FROM especialidad_protagonista WHERE id_protagonista = ? AND id_especialidad = ?',
            [$idProtagonista, $idEspecialidad],
        );
    }

    /** @return array<string, mixed> */
    private function parametros(EspecialidadProtagonista $ep): array
    {
        return [
            'id_protagonista' => $ep->idProtagonista,
            'id_especialidad' => $ep->idEspecialidad,
            'estado'          => $ep->estado->value,
            'fecha'           => $ep->fecha,
        ];
    }
}
