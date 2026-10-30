<?php
// Ruta: src/Servicios/ProgresionService.php
require_once __DIR__ . '/../Datos/ProgresionRepository.php';
require_once __DIR__ . '/../Datos/InsigniaRepository.php';
require_once __DIR__ . '/../Datos/CompetenciaRepository.php';

class ProgresionService {
    private CompetenciaRepository $repo;

    public function __construct() {
        $this->repo = new CompetenciaRepository();
    }

    /**
     * Marca o desmarca una competencia como desarrollada para un protagonista.
     */
    public function procesarCambioCompetencia(int $idProtagonista, int $idCompetencia, bool $desarrollada): array {
        $ok = $this->repo->marcarCompetencia($idProtagonista, $idCompetencia, $desarrollada);

        if (!$ok) {
            return ['success' => false, 'error' => 'La competencia indicada no existe.'];
        }

        return ['success' => true, 'desarrollada' => $desarrollada];
    }

    public function procesarCambioAccion(int $idProtagonista, int $idAccion, int $idCompetencia, string $nuevoEstado): array {
        $this->repo->actualizarEstadoAccion($idProtagonista, $idAccion, $nuevoEstado);
        
        $respuesta = ['success' => true, 'sugerencia' => null];

        if ($nuevoEstado === 'Realizada') {
            if ($this->repo->verificarAccionesCompletas($idProtagonista, $idCompetencia)) {
                $respuesta['sugerencia'] = [
                    'tipo' => 'COMPETENCIA_LISTA',
                    'id_competencia' => $idCompetencia,
                    'mensaje' => 'El protagonista ha realizado todas las acciones recomendadas. ¿Deseas dar por adquirida la competencia principal?'
                ];
            }
        }
        return $respuesta;
    }

    public function obtenerAlertas(int $idProtagonista): array {
        
        return []; 
    }
}
?>
