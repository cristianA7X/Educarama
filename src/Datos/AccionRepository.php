<?php
require_once __DIR__ . '/src/Model/Accion.php';

class AccionRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM accion");
        $items = [];
        while ($row = $stmt->fetch()) { $items[] = new Accion((int)$row['id'], $row['descripcion'], (int)$row['id_competencia'], (int)$row['codigo_competencia'], (int)$row['id_tipo_accion']); }
        return $items;
    }

    public function mostrarPorId(int $id): ?Accion {
        $stmt = $this->db->prepare("SELECT * FROM accion WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Accion((int)$row['id'], $row['descripcion'], (int)$row['id_competencia'], (int)$row['codigo_competencia'], (int)$row['id_tipo_accion']) : null;
    }

    public function agregar($accion): int {
        $stmt = $this->db->prepare("INSERT INTO accion (descripcion, id_competencia, codigo_competencia, id_tipo_accion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$accion->descripcion, $accion->id_competencia, $accion->codigo_competencia, $accion->id_tipo_accion]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar($accion): void {
        $stmt = $this->db->prepare("UPDATE accion SET descripcion = ?, id_competencia = ?, codigo_competencia = ?, id_tipo_accion = ? WHERE id = ?");
        $stmt->execute([$accion->descripcion, $accion->id_competencia, $accion->codigo_competencia, $accion->id_tipo_accion, $accion->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM accion WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>