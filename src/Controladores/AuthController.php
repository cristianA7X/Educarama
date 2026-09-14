<?php
// src/Controladores/AuthController.php
// Esta clase es responsable de manejar la lógica de autenticación de usuarios, 
// incluyendo el inicio de sesión y la verificación de credenciales.

require_once __DIR__ . '/../Datos/Database.php';
require_once __DIR__ . '/../Datos/UsuarioRepository.php';
require_once __DIR__ . '/../Servicios/LoginService.php';


header('Content-Type: application/json; charset=utf-8');

// se encarga 
try {
    // Verifica si el método de la solicitud es POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Método no permitido.'
        ]);
        exit;
    }
    // Obtiene la conexión a la base de datos
    $conexion = Database::getConexion();

    // Crea una instancia del repositorio de usuarios y del servicio de login
    $usuarioRepository = new UsuarioRepository($conexion);
    $loginService = new LoginService($usuarioRepository);

    // Llama al método de login del servicio (LoginService) con los datos del POST
    $resultado = $loginService->login($_POST);

    // Devuelve el resultado en formato JSON
    echo json_encode($resultado);
    exit;

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
    exit;
}