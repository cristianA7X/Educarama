<?php   

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../Datos/UsuarioRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/RegistrarUsuarioService.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));

if ($metodo === 'POST') {
    $nombre = $_POST['nombre'] ?? null;
    $email = $_POST['email'] ?? null;
    $contrasenia = $_POST['contrasenia'] ?? null;

    // Verificamos que lleguen los datos requeridos por el servicio
    if ($nombre && $email && $contrasenia) {
        
        try {
            $repo = new UsuarioRepository();
            $servicio = new RegistrarUsuarioService($repo);
            $servicio->ejecutar($nombre, $email, $contrasenia);    

            http_response_code(201); // 201 Created
            echo json_encode([
                "status" => "success",
                "message" => "Usuario registrado con éxito."
            ]);
            
        } catch (Exception $e) {
            http_response_code(400); // 400 Bad Request
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "Faltan datos obligatorios (nombre, email o contraseña)."
        ]);
    }
} else {
    http_response_code(405); // 405 Method Not Allowed
    echo json_encode([
        "status" => "error", 
        "message" => "Método no soportado. Se esperaba POST."
    ]);
}
?>