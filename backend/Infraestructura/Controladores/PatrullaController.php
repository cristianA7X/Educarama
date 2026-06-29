<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../Datos/PatrullaRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/PatrullaService.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));

$repo = new PatrullaRepository();
$servicio = new PatrullaService($repo);

try
{
    switch($metodo)
    {
        case 'GET':
            if(isset($_GET['id']))
            {
                $resultado = $servicio->obtenerPorId((int)$_GET['id']);
            }
            else
            {
                $resultado = $servicio->listarTodas();
            }
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $resultado]);
            break;
    }
    case 'POST':
        if(isset($data->nombre) && isset($data->id_rama))
        {
            $servicio->crear($data->nombre, (int)$data->id_rama);
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Patrulla creada con éxito."]);
        }
        else
        {
            throw new Exception("Faltan datos para crear la patrulla.");
        }
        break;
    case: 'PUT':
        if(isset($data->id) && isset($data->nombre) && isset($data->id_rama))
        {
            $servicio->actualizar((int)$data->id, $data->nombre, (int)$data->id_rama);
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Patrulla actualizada."]);
        }
        else
        {
            throw new Exception("Faltan datos para actualizar la patrulla.");
        }
        break;
    case 'DELETE':
        if(isset($_GET['id']))
        {
            $servicio->eliminar((int)$_GET['id']);
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Patrulla eliminada."]);
        }
        else
        {
            throw new Exception("Falta el ID para eliminar.");
        }
}