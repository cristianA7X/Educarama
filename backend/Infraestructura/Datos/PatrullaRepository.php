<?php
require_once __DIR__ . '/../../Dominio/Model/Patrulla.php';

class PatrullaRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM patrulla");
        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = new Patrulla((int)$row['id'], $row['nombre'], $row['id_rama'] ? (int)$row['id_rama'] : null);
        }
        return $items;
    }

    public function mostrarPorId(int $id): ?Patrulla {
        $stmt = $this->db->prepare("SELECT * FROM patrulla WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Patrulla((int)$row['id'], $row['nombre'], $row['id_rama'] ? (int)$row['id_rama'] : null) : null;
    }

    public function agregar($patrulla): void {
        $stmt = $this->db->prepare("INSERT INTO patrulla (nombre, id_rama) VALUES (?, ?)");
        $stmt->execute([$patrulla->nombre, $patrulla->id_rama]);
    }

    public function actualizar($patrulla): void {
        $stmt = $this->db->prepare("UPDATE patrulla SET nombre = ?, id_rama = ? WHERE id = ?");
        $stmt->execute([$patrulla->nombre, $patrulla->id_rama, $patrulla->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM patrulla WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>