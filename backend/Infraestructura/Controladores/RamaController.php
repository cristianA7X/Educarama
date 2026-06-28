<?php
// backend/Infraestructura/Controladores/RamaController.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../Datos/RamaRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/RamaService.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));

// Instanciamos nuestras capas
$repo = new RamaRepository();
$servicio = new RamaService($repo);

try {
    switch ($metodo) {
        case 'GET':
            // Si pasan un ID (?id=1), traemos una. Si no, traemos todas.
            if (isset($_GET['id'])) {
                $resultado = $servicio->obtenerPorId((int)$_GET['id']);
            } else {
                $resultado = $servicio->listarTodas();
            }
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;

        case 'POST':
            // Crear nueva rama
            if (isset($data->nombre) && isset($data->id_grupo) && isset($data->id_rama)) {
                $servicio->crear($data->nombre, (int)$data->id_grupo, (int)$data->id_rama);
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Rama creada con éxito."]);
            } else {
                throw new Exception("Faltan datos para crear la rama.");
            }
            break;

        case 'PUT':
            // Actualizar rama existente
            if (isset($data->id) && isset($data->nombre) && isset($data->id_grupo) && isset($data->id_rama)) {
                $servicio->actualizar((int)$data->id, $data->nombre, (int)$data->id_grupo, (int)$data->id_rama);
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Rama actualizada."]);
            } else {
                throw new Exception("Faltan datos para actualizar la rama.");
            }
            break;

        case 'DELETE':
            // Eliminar rama
            if (isset($_GET['id'])) {
                $servicio->eliminar((int)$_GET['id']);
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Rama eliminada."]);
            } else {
                throw new Exception("Falta el ID para eliminar.");
            }
            break;

        default:
            throw new Exception("Método no soportado.");
    }

} catch (Exception $e) {
    http_response_code(400); // 400 Bad Request
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>