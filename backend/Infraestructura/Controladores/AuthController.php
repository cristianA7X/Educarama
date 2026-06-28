<?php
// backend/Infraestructura/Controladores/AuthController.php

// Permitir solicitudes desde el frontend (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../Datos/UsuarioRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/LoginService.php';

// Leemos el JSON que manda el frontend
$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data->email) && isset($data->contrasenia)) {
    try {
        $repo = new UsuarioRepository();
        $servicio = new LoginService($repo);
        
        $usuario = $servicio->ejecutar($data->email, $data->contrasenia);
        
        // Respuesta exitosa para el frontend
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Login exitoso",
            "data" => $usuario
        ]);
        
    } catch (Exception $e) {
        // Error de credenciales
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} else {
    // Faltan datos
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos de login."]);
}
?>