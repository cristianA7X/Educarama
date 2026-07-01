<?php

class PersonaRepository
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function crear(array $datos): int
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO persona
            (apellido, nombre, fecha_nacimiento, dni)
            VALUES
            (:apellido, :nombre, :fecha_nacimiento, :dni)
        ");

        $stmt->execute([
            ':apellido' => $datos['apellido'],
            ':nombre' => $datos['nombre'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':dni' => $datos['dni']
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(int $idPersona, array $datos): void
    {
        $stmt = $this->conexion->prepare("
            UPDATE persona
            SET apellido = :apellido,
                nombre = :nombre,
                fecha_nacimiento = :fecha_nacimiento,
                dni = :dni
            WHERE id = :id
        ");

        $stmt->execute([
            ':apellido' => $datos['apellido'],
            ':nombre' => $datos['nombre'],
            ':fecha_nacimiento' => $datos['fecha_nacimiento'],
            ':dni' => $datos['dni'],
            ':id' => $idPersona
        ]);
    }
}