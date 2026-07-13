<?php
require_once __DIR__ . '/src/Dominio/Model/TipoAccion.php';

class TipoAccionRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM tipo_accion");
        $items = [];
        while ($row = $stmt->fetch()) { $items[] = new TipoAccion((int)$row['id'], $row['tipo']); }
        return $items;
    }

    public function mostrarPorId(int $id): ?TipoAccion {
        $stmt = $this->db->prepare("SELECT * FROM tipo_accion WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new TipoAccion((int)$row['id'], $row['tipo']) : null;
    }

    public function agregar($tipo): void {
        $stmt = $this->db->prepare("INSERT INTO tipo_accion (tipo) VALUES (?)");
        $stmt->execute([$tipo->tipo]);
    }

    public function actualizar($tipo): void {
        $stmt = $this->db->prepare("UPDATE tipo_accion SET tipo = ? WHERE id = ?");
        $stmt->execute([$tipo->tipo, $tipo->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM tipo_accion WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>