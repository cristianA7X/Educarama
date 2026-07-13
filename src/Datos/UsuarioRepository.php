<?php

require_once __DIR__ . '/../../Dominio/Repositorio/MostrarTodoInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/MostrarPorIdInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/AgregarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/ActualizarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/EliminarInterface.php';
require_once __DIR__ . '/../../Dominio/Model/Usuario.php';
require_once 'Database.php'; // Tu archivo de conexión

/**
 * @implements MostrarTodoInterface<Usuario>
 * @implements MostrarPorIdInterface<Usuario>
 */
class UsuarioRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface
{
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConexion();
    }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM usuario");
        $usuarios = [];
        while ($row = $stmt->fetch()) {
            $usuarios[] = new Usuario((int)$row['id'], $row['nombre'], $row['email'], $row['contrasenia']);
        }
        return $usuarios;
    }

    public function mostrarPorId(int $id): ?Usuario {
        $stmt = $this->db->prepare("SELECT * FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) return null;
        return new Usuario((int)$row['id'], $row['nombre'], $row['email'], $row['contrasenia']);
    }

    public function agregar($usuario): int {
        $stmt = $this->db->prepare("INSERT INTO usuario (nombre, email, contrasenia) VALUES (?, ?, ?)");
        $stmt->execute([$usuario->nombre, $usuario->email, $usuario->contrasenia]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar($usuario): void {
        $stmt = $this->db->prepare("UPDATE usuario SET nombre = ?, email = ?, contrasenia = ? WHERE id = ?");
        $stmt->execute([$usuario->nombre, $usuario->email, $usuario->contrasenia, $usuario->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
    }

    // --- MÉTODOS PARA TABLAS INTERMEDIAS ---
    
    public function asignarRol(int $idUsuario, int $idRol, ?int $idGrupo = null, ?int $idRama = null): void {
        $stmt = $this->db->prepare("INSERT INTO asignacion_rol (id_usuario, id_rol, id_grupo, id_rama) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idUsuario, $idRol, $idGrupo, $idRama]);
    }

    public function buscarPorEmail(string $email): ?Usuario 
    {
        $stmt = $this->db->prepare("SELECT * FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        
        if (!$row) return null;
        return new Usuario((int)$row['id'], $row['nombre'], $row['email'], $row['contrasenia']);
    }
}
?>