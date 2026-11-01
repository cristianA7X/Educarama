<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/src/Datos/CategoriaRamaRepository.php';
require_once __DIR__ . '/src/Servicios/CategoriaRamaService.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));

$repo = new CategoriaRamaRepository();
$servicio = new CategoriaRamaService($repo);

try {
    switch ($metodo) {
        case 'GET':
            if (isset($_GET['id'])) {
                $resultado = $servicio->obtenerPorId((int)$_GET['id']);
            } else {
                $resultado = $servicio->listarTodas();
            }
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;

        case 'POST':
            if (isset($data->nombre)) {
                $servicio->crear($data->nombre);
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Categoría de rama creada con éxito."]);
            } else {
                throw new Exception("Falta el nombre para crear la categoría.");
            }
            break;

        case 'PUT':
            if (isset($data->id) && isset($data->nombre)) {
                $servicio->actualizar((int)$data->id, $data->nombre);
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Categoría de rama actualizada."]);
            } else {
                throw new Exception("Faltan datos para actualizar la categoría.");
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $servicio->eliminar((int)$_GET['id']);
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Categoría de rama eliminada."]);
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