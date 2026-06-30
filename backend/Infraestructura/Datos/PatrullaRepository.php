<?php

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
                r.nombre AS rama
            FROM patrulla p
            LEFT JOIN rama r ON p.id_rama = r.id
            ORDER BY p.nombre
        ");

        $stmt->execute();

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
}
?>