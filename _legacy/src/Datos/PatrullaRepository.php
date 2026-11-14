<?php
// src/Datos/PatrullaRepository.php

class PatrullaRepository
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function listarTodo(): array
    {
        $stmt = $this->conexion->prepare("
            SELECT 
                p.id,
                p.nombre,
                p.id_rama,
                r.nombre AS rama,
                r.id_rama AS id_categoria_rama
            FROM patrulla p
            LEFT JOIN rama r ON p.id_rama = r.id
            ORDER BY p.nombre
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT 
                p.id,
                p.nombre,
                p.id_rama,
                r.nombre AS rama,
                r.id_rama AS id_categoria_rama
            FROM patrulla p
            LEFT JOIN rama r ON p.id_rama = r.id
            WHERE p.id = :id
        ");

        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function obtenerIntegrantes(int $idPatrulla): array 
    {
        // Consultamos la tabla protagonista unida a persona, NO a integrante
        $stmt = $this->conexion->prepare("
            SELECT 
                p.id, 
                p.anio_ingreso, 
                per.nombre, 
                per.apellido, 
                per.fecha_nacimiento
            FROM protagonista p
            INNER JOIN persona per ON p.id_persona = per.id
            WHERE p.id_patrulla = :id_patrulla
            ORDER BY per.apellido ASC
        ");
        
        $stmt->execute(['id_patrulla' => $idPatrulla]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $datos): void
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO patrulla
            (nombre, id_rama)
            VALUES
            (:nombre, :id_rama)
        ");

        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':id_rama' => $datos['id_rama']
        ]);
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->conexion->prepare("
            UPDATE patrulla
            SET nombre = :nombre, id_rama = :id_rama
            WHERE id = :id
        ");

        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':id_rama' => $datos['id_rama'],
            ':id' => $id
        ]);
    }
}
?>