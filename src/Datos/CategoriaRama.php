<?php
require_once __DIR__ . '/src/Model/CategoriaRama.php';

class CategoriaRamaRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { 
        $this->db = Database::getConexion(); 
    }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM categoria_rama");
        $items = [];
        while ($row = $stmt->fetch()) { $items[] = new CategoriaRama((int)$row['id'], $row['nombre']); }
        return $items;
    }

    public function mostrarPorId(int $id): ?CategoriaRama {
        $stmt = $this->db->prepare("SELECT * FROM categoria_rama WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new CategoriaRama((int)$row['id'], $row['nombre']) : null;
    }

public function agregar($categoria): int {
    $stmt = $this->db->prepare("INSERT INTO categoria_rama (nombre) VALUES (?)");
    $stmt->execute([$categoria->nombre]);    
    return (int) $this->db->lastInsertId();
}

    public function actualizar($categoria): void {
        $stmt = $this->db->prepare("UPDATE categoria_rama SET nombre = ? WHERE id = ?");
        $stmt->execute([$categoria->nombre, $categoria->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM categoria_rama WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>