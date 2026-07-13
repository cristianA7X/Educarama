<?php
require_once __DIR__ . '/src/Model/Competencia.php';

class CompetenciaRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM competencia");
        $items = [];
        while ($row = $stmt->fetch()) { $items[] = new Competencia((int)$row['id'], (int)$row['codigo'], $row['descripcion'], (int)$row['id_categoria_rama'], (int)$row['id_sendero']); }
        return $items;
    }

    public function mostrarPorId(int $id): ?Competencia {
        $stmt = $this->db->prepare("SELECT * FROM competencia WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Competencia((int)$row['id'], (int)$row['codigo'], $row['descripcion'], (int)$row['id_categoria_rama'], (int)$row['id_sendero']) : null;
    }

    public function agregar($competencia): void {
        $stmt = $this->db->prepare("INSERT INTO competencia (codigo, descripcion, id_categoria_rama, id_sendero) VALUES (?, ?, ?, ?)");
        $stmt->execute([$competencia->codigo, $competencia->descripcion, $competencia->id_categoria_rama, $competencia->id_sendero]);
    }

    public function actualizar($competencia): void {
        $stmt = $this->db->prepare("UPDATE competencia SET codigo = ?, descripcion = ?, id_categoria_rama = ?, id_sendero = ? WHERE id = ?");
        $stmt->execute([$competencia->codigo, $competencia->descripcion, $competencia->id_categoria_rama, $competencia->id_sendero, $competencia->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM competencia WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>