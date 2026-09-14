<?php
require_once __DIR__ . '/src/Dominio/Model/Rol.php';

class RolRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM rol");
        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = new Rol((int)$row['id'], $row['nombre']);
        }
        return $items;
    }

    public function mostrarPorId(int $id): ?Rol {
        $stmt = $this->db->prepare("SELECT * FROM rol WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Rol((int)$row['id'], $row['nombre']) : null;
    }

    public function agregar($rol): int {
        $stmt = $this->db->prepare("INSERT INTO rol (nombre) VALUES (?)");
        $stmt->execute([$rol->nombre]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar($rol): void {
        $stmt = $this->db->prepare("UPDATE rol SET nombre = ? WHERE id = ?");
        $stmt->execute([$rol->nombre, $rol->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM rol WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>