<?php
require_once __DIR__ . '/../../Dominio/Model/Rama.php';

class RamaRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM rama");
        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = new Rama((int)$row['id'], $row['nombre'], (int)$row['id_grupo'], (int)$row['id_rama']);
        }
        return $items;
    }

    public function mostrarPorId(int $id): ?Rama {
        $stmt = $this->db->prepare("SELECT * FROM rama WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Rama((int)$row['id'], $row['nombre'], (int)$row['id_grupo'], (int)$row['id_rama']) : null;
    }

    public function agregar($rama): void {
        $stmt = $this->db->prepare("INSERT INTO rama (nombre, id_grupo, id_rama) VALUES (?, ?, ?)");
        $stmt->execute([$rama->nombre, $rama->id_grupo, $rama->id_rama]);
    }

    public function actualizar($rama): void {
        $stmt = $this->db->prepare("UPDATE rama SET nombre = ?, id_grupo = ?, id_rama = ? WHERE id = ?");
        $stmt->execute([$rama->nombre, $rama->id_grupo, $rama->id_rama, $rama->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM rama WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>