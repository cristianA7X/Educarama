<?php
// backend/Infraestructura/Controladores/ProtagonistaController.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../Datos/ProtagonistaRepository.php';
require_once __DIR__ . '/../Datos/PersonaRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/ProtagonistaService.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true); // true para que sea array asociativo

// Instanciamos repositorios y el servicio
$protaRepo = new ProtagonistaRepository();
$personaRepo = new PersonaRepository();
$servicio = new ProtagonistaService($protaRepo, $personaRepo);

try {
    switch ($metodo) {
        case 'GET':
            if (isset($_GET['id'])) {
                $resultado = $servicio->obtenerPorId((int)$_GET['id']);
            } else {
                $resultado = $servicio->listarTodos();
            }
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;

        case 'POST':
            // Verificamos si es una acción especial (asignar rama) o una creación normal
            if (isset($_GET['accion']) && $_GET['accion'] === 'asignar_rama') {
                if (isset($data['id_protagonista']) && isset($data['id_rama'])) {
                    $servicio->asignarRama((int)$data['id_protagonista'], (int)$data['id_rama']);
                    http_response_code(200);
                    echo json_encode(["status" => "success", "message" => "Protagonista asignado a la nueva rama con éxito."]);
                } else {
                    throw new Exception("Faltan id_protagonista o id_rama.");
                }
            } else {
                // Creación normal
                $servicio->crear($data);
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Protagonista creado con éxito."]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $servicio->eliminar((int)$_GET['id']);
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Protagonista eliminado."]);
            } else {
                throw new Exception("Falta el ID para eliminar.");
            }
            break;

        default:
            throw new Exception("Método no soportado.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>