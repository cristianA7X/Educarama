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
    header('Content-Type: application/json; charset=utf-8');

    // Endpoint AJAX: respondemos 401 en JSON en vez de redirigir al login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Sesión expirada. Volvé a iniciar sesión.']);
        exit;
    }

    $servicio = new ProgresionService();
    
    try {
        if ($input['tipo_actualizacion'] === 'competencia') {
            $idProtagonista = (int) ($input['id_protagonista'] ?? 0);
            $idCompetencia  = (int) ($input['id_competencia'] ?? 0);

            if ($idProtagonista <= 0 || $idCompetencia <= 0 || !isset($input['desarrollada'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
                exit;
            }

            $resultado = $servicio->procesarCambioCompetencia(
                $idProtagonista,
                $idCompetencia,
                filter_var($input['desarrollada'], FILTER_VALIDATE_BOOLEAN)
            );

            if (!$resultado['success']) {
                http_response_code(404);
            }
            echo json_encode($resultado);
            exit;
        }

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
