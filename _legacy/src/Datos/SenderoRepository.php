<?php
require_once __DIR__ . '/src/Model/Sendero.php';

class SenderoRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM sendero");
        $items = [];
        while ($row = $stmt->fetch()) { $items[] = new Sendero((int)$row['id'], $row['tipo']); }
        return $items;
    }

    public function mostrarPorId(int $id): ?Sendero {
        $stmt = $this->db->prepare("SELECT * FROM sendero WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Sendero((int)$row['id'], $row['tipo']) : null;
    }

    public function agregar($sendero): void {
        $stmt = $this->db->prepare("INSERT INTO sendero (tipo) VALUES (?)");
        $stmt->execute([$sendero->tipo]);
    }

    public function actualizar($sendero): void {
        $stmt = $this->db->prepare("UPDATE sendero SET tipo = ? WHERE id = ?");
        $stmt->execute([$sendero->tipo, $sendero->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM sendero WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>