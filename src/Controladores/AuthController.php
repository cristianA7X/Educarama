<?php

// 1. ARRANCAR LA SESIÓN DE PHP ANTES DE CUALQUIER COSA
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../Datos/UsuarioRepository.php'; // Ajusté la ruta relativa
require_once __DIR__ . '/../Servicios/LoginService.php'; // Ajusté la ruta relativa

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si viene por JSON (fetch) o por $_POST tradicional
    $email = $data->email ?? $_POST['email'] ?? null;
    $contrasenia = $data->contrasenia ?? $_POST['contrasenia'] ?? null;

    if ($email && $contrasenia) {
        try {
            $repo = new UsuarioRepository();
            $servicio = new LoginService($repo);
            
            // Si las credenciales están mal, esto lanza una Exception
            $usuario = $servicio->ejecutar($email, $contrasenia);
            
            // 2. CREDENCIALES CORRECTAS: GUARDAMOS EN SESIÓN
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            
            // Respuesta exitosa para el frontend
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login exitoso",
                "data" => $usuario
            ]);
            
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Faltan datos de login."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
}
?>