<?php
// src/Repository/PersonaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Persona;

/**
 * Lectura y escritura de `persona`: los datos personales que comparten
 * protagonistas y familiares. Una misma persona puede ser las dos cosas.
 * No tiene eliminar: borrar una persona lo decide el Service cuando ya no
 * es ni protagonista ni familiar (se resuelve con la baja lógica).
 */
final class PersonaRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Persona
    {
        return $this->buscarUno('SELECT * FROM persona WHERE id = ?', [$id], Persona::class);
    }

    /**
     * El DNI es único en toda la base. Sirve para no duplicar a alguien que
     * ya está cargado (por ejemplo, un familiar de otro protagonista).
     */
    public function buscarPorDni(int $dni): ?Persona
    {
        return $this->buscarUno('SELECT * FROM persona WHERE dni = ?', [$dni], Persona::class);
    }

    /** Guarda una persona nueva, le completa el id y lo devuelve. */
    public function insertar(Persona $persona): int
    {
        $persona->id = $this->insertarFila(
            'INSERT INTO persona (dni, nombre, apellido, fecha_nacimiento)
             VALUES (:dni, :nombre, :apellido, :fecha_nacimiento)',
            [
                'dni'              => $persona->dni,
                'nombre'           => $persona->nombre,
                'apellido'         => $persona->apellido,
                'fecha_nacimiento' => $persona->fechaNacimiento,
            ],
        );

        return $persona->id;
    }

    public function actualizar(Persona $persona): void
    {
        $this->ejecutar(
            'UPDATE persona
                SET dni = :dni, nombre = :nombre, apellido = :apellido, fecha_nacimiento = :fecha_nacimiento
              WHERE id = :id',
            [
                'dni'              => $persona->dni,
                'nombre'           => $persona->nombre,
                'apellido'         => $persona->apellido,
                'fecha_nacimiento' => $persona->fechaNacimiento,
                'id'               => $this->exigirId($persona->id),
            ],
        );
    }
}
