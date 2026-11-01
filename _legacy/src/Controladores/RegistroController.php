<?php
// src/Controladores/RegistroController.php
// Esta clase es responsable de manejar la lógica de registro de usuarios,
// incluyendo la validación de datos y la creación de nuevos usuarios en la base de datos.

require_once __DIR__ . '/../Datos/Database.php';
require_once __DIR__ . '/../Datos/UsuarioRepository.php';
require_once __DIR__ . '/../Servicios/RegistrarUsuarioService.php';

header('Content-Type: application/json; charset=utf-8');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Método no permitido.'
        ]);
        exit;
    }
    // Obtiene la conexión a la base de datos
    $conexion = Database::getConexion();
    $usuarioRepository = new UsuarioRepository($conexion);
    $registrarUsuarioService = new RegistrarUsuarioService($usuarioRepository);

    // Llama al método de registro del servicio (RegistrarUsuarioService) con los datos del POST
    $resultado = $registrarUsuarioService->registrar($_POST);

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