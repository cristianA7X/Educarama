<?php
// Ruta: backend/Infraestructura/Datos/ProtagonistaRepository.php

require_once __DIR__ . '/../../Dominio/Repositorio/MostrarTodoInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/MostrarPorIdInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/AgregarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/ActualizarInterface.php';
require_once __DIR__ . '/../../Dominio/Repositorio/EliminarInterface.php';
require_once __DIR__ . '/../../Dominio/Model/Protagonista.php';
require_once 'Database.php';

/**
 * @implements MostrarTodoInterface<Protagonista>
 * @implements MostrarPorIdInterface<Protagonista>
 */
class ProtagonistaRepository implements MostrarTodoInterface, MostrarPorIdInterface, AgregarInterface, ActualizarInterface, EliminarInterface
{
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConexion();
    }

    public function mostrarTodo(): array {
        $stmt = $this->db->query("SELECT * FROM protagonista");
        $protagonistas = [];
        while ($row = $stmt->fetch()) {
            $protagonistas[] = new Protagonista((int)$row['id'], (bool)$row['promesa'], (int)$row['id_persona'], (int)$row['id_rama'], $row['id_patrulla'] ? (int)$row['id_patrulla'] : null, $row['id_usuario'] ? (int)$row['id_usuario'] : null);
        }
        return $protagonistas;
    }

    public function mostrarPorId(int $id): ?Protagonista {
        $stmt = $this->db->prepare("SELECT * FROM protagonista WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) return null;
        return new Protagonista((int)$row['id'], (bool)$row['promesa'], (int)$row['id_persona'], (int)$row['id_rama'], $row['id_patrulla'] ? (int)$row['id_patrulla'] : null, $row['id_usuario'] ? (int)$row['id_usuario'] : null);
    }

    public function agregar($protagonista): int {
        $stmt = $this->db->prepare("INSERT INTO protagonista (promesa, id_persona, id_rama, id_patrulla, id_usuario) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([(int)$protagonista->promesa, $protagonista->id_persona, $protagonista->id_rama, $protagonista->id_patrulla, $protagonista->id_usuario]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar($protagonista): void {
        $stmt = $this->db->prepare("UPDATE protagonista SET promesa = ?, id_persona = ?, id_rama = ?, id_patrulla = ?, id_usuario = ? WHERE id = ?");
        $stmt->execute([(int)$protagonista->promesa, $protagonista->id_persona, $protagonista->id_rama, $protagonista->id_patrulla, $protagonista->id_usuario, $protagonista->id]);
    }

    public function eliminar(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM protagonista WHERE id = ?");
        $stmt->execute([$id]);
    }

    // --- MÉTODOS PARA TABLAS INTERMEDIAS (Progresión del Scout) ---

    public function registrarAccionCumplida(int $idProtagonista, int $idAccion, bool $cumplida = true): void {
        $stmt = $this->db->prepare("INSERT INTO cumple (id_protagonista, id_accion, accion_cumplida) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE accion_cumplida = ?");
        $stmt->execute([$idProtagonista, $idAccion, (int)$cumplida, (int)$cumplida]);
    }

    public function registrarCompetenciaDesarrollada(int $idProtagonista, int $idCompetencia, int $codigoCompetencia, bool $desarrollada = true): void {
        $stmt = $this->db->prepare("INSERT INTO desarrolla (id_protagonista, id_competencia, codigo_competencia, competencia_desarrollada) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE competencia_desarrollada = ?");
        $stmt->execute([$idProtagonista, $idCompetencia, $codigoCompetencia, (int)$desarrollada, (int)$desarrollada]);
    }

    public function registrarInsigniaObtenida(int $idProtagonista, int $idInsignia, string $fecha): void {
        $stmt = $this->db->prepare("INSERT INTO obtiene (id_protagonista, id_insignia, fecha) VALUES (?, ?, ?)");
        $stmt->execute([$idProtagonista, $idInsignia, $fecha]);
    }
}
?>