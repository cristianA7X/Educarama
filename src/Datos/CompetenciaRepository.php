<?php
// src/Datos/CompetenciaRepository.php

require_once __DIR__ . '/../Model/Competencia.php';
require_once __DIR__ . '/Database.php';

class CompetenciaRepository {
    private PDO $conexion;

    public function __construct() {
        $this->conexion = Database::getConexion();
    }

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