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
}
require_once __DIR__ . '/../../Dominio/Repositorio/MostrarTodoInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/MostrarPorIdInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/AgregarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/ActualizarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/EliminarInterface.php';
require_once __DIR__ . '/../../Dominio/Model/Persona.php';
require_once 'Database.php';


?>