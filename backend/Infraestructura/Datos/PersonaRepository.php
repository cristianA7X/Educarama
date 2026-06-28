<?php
require_once __DIR__ . '/../../Dominio/Repositorio/MostrarTodoInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/MostrarPorIdInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/AgregarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/ActualizarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/EliminarInterface.php';
require_once __DIR__ . '/../../Dominio/Model/Persona.php';
require_once 'Database.php';

/**
 * @implements MostrarTodoInterface<Persona>
 * @implements MostrarPorIdInterface<Persona>
 */
class PersonaRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface {
    private PDO $db;
    public function __construct() { $this->db = Database::getConexion(); }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM persona");
        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = new Persona((int)$row['id'], (int)$row['dni'], $row['nombre'], $row['apellido'], $row['fecha_nacimiento']);
        }
        return $items;
    }

    public function mostrarPorId(int $id): ?Persona {
        $stmt = $this->db->prepare("SELECT * FROM persona WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new Persona((int)$row['id'], (int)$row['dni'], $row['nombre'], $row['apellido'], $row['fecha_nacimiento']) : null;
    }

    public function agregar($persona): int {
        $stmt = $this->db->prepare("INSERT INTO persona (dni, nombre, apellido, fecha_nacimiento) VALUES (?, ?, ?, ?)");
        $stmt->execute([$persona->dni, $persona->nombre, $persona->apellido, $persona->fecha_nacimiento]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar($persona): void {
        $stmt = $this->db->prepare("UPDATE persona SET dni = ?, nombre = ?, apellido = ?, fecha_nacimiento = ? WHERE id = ?");
        $stmt->execute([$persona->dni, $persona->nombre, $persona->apellido, $persona->fecha_nacimiento, $persona->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM persona WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>