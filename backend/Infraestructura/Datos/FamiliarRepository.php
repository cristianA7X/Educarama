<?php
// Ruta: backend/Infraestructura/Datos/FamiliarRepository.php

require_once __DIR__ . '/../../Dominio/Repositorio/MostrarTodoInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/MostrarPorIdInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/AgregarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/ActualizarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/EliminarInterface.php';
require_once __DIR__ . '/../../Dominio/Model/Familiar.php';
require_once 'Database.php';

/**
 * @implements MostrarTodoInterface<Familiar>
 * @implements MostrarPorIdInterface<Familiar>
 */
class FamiliarRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface
{
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConexion();
    }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM familiar");
        $familiares = [];
        while ($row = $stmt->fetch()) {
            $familiares[] = new Familiar((int)$row['id'], $row['telefono_contacto'] ? (int)$row['telefono_contacto'] : null, $row['email_contacto'], $row['id_persona'] ? (int)$row['id_persona'] : null);
        }
        return $familiares;
    }

    public function mostrarPorId(int $id): ?Familiar {
        $stmt = $this->db->prepare("SELECT * FROM familiar WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) return null;
        return new Familiar((int)$row['id'], $row['telefono_contacto'] ? (int)$row['telefono_contacto'] : null, $row['email_contacto'], $row['id_persona'] ? (int)$row['id_persona'] : null);
    }

    public function agregar($familiar): void {
        $stmt = $this->db->prepare("INSERT INTO familiar (telefono_contacto, email_contacto, id_persona) VALUES (?, ?, ?)");
        $stmt->execute([$familiar->telefono_contacto, $familiar->email_contacto, $familiar->id_persona]);
    }

    public function actualizar($familiar): void {
        $stmt = $this->db->prepare("UPDATE familiar SET telefono_contacto = ?, email_contacto = ?, id_persona = ? WHERE id = ?");
        $stmt->execute([$familiar->telefono_contacto, $familiar->email_contacto, $familiar->id_persona, $familiar->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM familiar WHERE id = ?");
        $stmt->execute([$id]);
    }

    // --- MÉTODOS PARA TABLAS INTERMEDIAS ---

    public function vincularAProtagonista(int $idProtagonista, int $idFamiliar, string $vinculo, bool $esContactoEmergencia): void {
        $stmt = $this->db->prepare("INSERT INTO es_familia (id_protagonista, id_familiar, vinculo, es_contacto_de_emergencia) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idProtagonista, $idFamiliar, $vinculo, (int)$esContactoEmergencia]);
    }
}
?>