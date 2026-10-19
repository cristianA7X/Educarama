<?php
///src/Controladores/ProgresionController.php

require_once __DIR__ . '/../Datos/Database.php';
require_once __DIR__ . '/../Datos/PersonaRepository.php';
require_once __DIR__ . '/../Datos/ProtagonistaRepository.php';
require_once __DIR__ . '/../Datos/RamaRepository.php';
require_once __DIR__ . '/../Datos/PatrullaRepository.php';
require_once __DIR__ . '/../Servicios/ProgresionService.php';

// Leemos el JSON enviado por Fetch
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['tipo_actualizacion'])) {
    $servicio = new ProgresionService();
    
    try {
        if ($input['tipo_actualizacion'] === 'accion') {
            $resultado = $servicio->procesarCambioAccion(
                $input['id_protagonista'], 
                $input['id_accion'], 
                $input['id_competencia'],
                $input['estado']
            );
            
            echo json_encode($resultado);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

}