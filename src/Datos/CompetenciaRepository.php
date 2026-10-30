<?php
// src/Datos/CompetenciaRepository.php

require_once __DIR__ . '/../Model/Competencia.php';
require_once __DIR__ . '/Database.php';

class CompetenciaRepository {
    private PDO $conexion;

    public function __construct() {
        $this->conexion = Database::getConexion();
    }

    /**
     * Todos los senderos (pestañas), siempre, aunque no tengan competencias.
     */
    public function obtenerSenderos(): array {
        $stmt = $this->conexion->query("SELECT id, tipo FROM sendero ORDER BY id");
        return $stmt->fetchAll();
    }

    /**
     * Competencias de la categoría de rama del protagonista (rama.id_rama = categoria_rama.id),
     * con el estado actual (desarrollada 0/1) para ese protagonista.
     */
    public function obtenerCompetenciasPorProtagonista(int $idProtagonista): array {
        $stmt = $this->conexion->prepare("
            SELECT
                c.id,
                c.codigo,
                c.descripcion,
                c.id_sendero,
                COALESCE(d.competencia_desarrollada, 0) AS desarrollada
            FROM protagonista p
            INNER JOIN rama r ON r.id = p.id_rama
            INNER JOIN competencia c ON c.id_categoria_rama = r.id_rama
            LEFT JOIN desarrolla d
                   ON d.id_protagonista   = p.id
                  AND d.id_competencia    = c.id
                  AND d.codigo_competencia = c.codigo
            WHERE p.id = :id_prota
            ORDER BY c.id_sendero, c.codigo
        ");
        $stmt->execute([':id_prota' => $idProtagonista]);
        return $stmt->fetchAll();
    }

    /**
     * Marca / desmarca una competencia como desarrollada.
     * Busca el código en la tabla competencia (la PK de desarrolla es id + codigo).
     * Devuelve false si la competencia no existe.
     */
    public function marcarCompetencia(int $idProtagonista, int $idCompetencia, bool $desarrollada): bool {
        $stmt = $this->conexion->prepare("
            INSERT INTO desarrolla (id_protagonista, id_competencia, codigo_competencia, competencia_desarrollada)
            SELECT :id_prota, c.id, c.codigo, :valor
            FROM competencia c
            WHERE c.id = :id_comp
            ON DUPLICATE KEY UPDATE competencia_desarrollada = VALUES(competencia_desarrollada)
        ");
        $stmt->execute([
            ':id_prota' => $idProtagonista,
            ':valor'    => (int) $desarrollada,
            ':id_comp'  => $idCompetencia,
        ]);

        // rowCount: 1 = insert, 2 = update con cambios, 0 = update sin cambios o competencia inexistente
        if ($stmt->rowCount() > 0) {
            return true;
        }
        $check = $this->conexion->prepare("SELECT COUNT(*) FROM competencia WHERE id = :id_comp");
        $check->execute([':id_comp' => $idCompetencia]);
        return (int) $check->fetchColumn() > 0;
    }

    // --- Acciones (se mantiene lo existente) ---

    public function actualizarEstadoAccion(int $idProtagonista, int $idAccion, string $estado): void {
        $stmt = $this->conexion->prepare("
            UPDATE protagonista_accion 
            SET estado = :estado 
            WHERE id_protagonista = :id_prota AND id_accion = :id_accion
        ");
        $stmt->execute([
            ':estado' => $estado,
            ':id_prota' => $idProtagonista,
            ':id_accion' => $idAccion
        ]);
    }

    public function verificarAccionesCompletas(int $idProtagonista, int $idCompetencia): bool {
        // Retorna true si el COUNT de acciones totales coincide con el COUNT de acciones 'Realizada'
        $stmt = $this->conexion->prepare("
            SELECT 
                (SELECT COUNT(*) FROM accion WHERE id_competencia = :id_comp) AS totales,
                (SELECT COUNT(*) FROM protagonista_accion WHERE id_protagonista = :id_prota AND estado = 'Realizada') AS realizadas
        ");
        $stmt->execute([':id_comp' => $idCompetencia, ':id_prota' => $idProtagonista]);
        $resultado = $stmt->fetch();
        
        return $resultado['totales'] === $resultado['realizadas'] && $resultado['totales'] > 0;
    }

}
?>
