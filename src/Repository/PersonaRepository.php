<?php // src/Repository/PersonaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Persona;
use PDO;

final class PersonaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Persona
    {
        return $this->buscarUno('SELECT * FROM persona WHERE id = ?', [$id], Persona::class);
    }

    public function buscarPorDni(int $dni): ?Persona
    {
        return $this->buscarUno('SELECT * FROM persona WHERE dni = ?', [$dni], Persona::class);
    }

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