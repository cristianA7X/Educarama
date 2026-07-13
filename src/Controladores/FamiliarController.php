<?php
// backend/Infraestructura/Controladores/FamiliarController.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8"); 

require_once __DIR__ . '/src/Datos/FamiliarRepository.php';
require_once __DIR__ . '/src/Datos/PersonaRepository.php';
require_once __DIR__ . '/src/Servicios/FamiliarService.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'POST' && isset($_POST['_method'])) {
    $metodo = strtoupper($_POST['_method']);
}

$familiarRepo = new FamiliarRepository();
$personaRepo = new PersonaRepository();
$servicio = new FamiliarService($familiarRepo, $personaRepo);

try {
    switch ($metodo) {
        case 'GET':
            if (isset($_GET['id'])) {
                $resultado = $servicio->obtenerPorId((int)$_GET['id']);
            } else {
                $resultado = $servicio->listarTodos();
            }
            echo "<h1>Consulta de Familiar procesada</h1>";
            break;

        case 'POST':
            $servicio->crear($_POST);
            header("Location: ../../../index.html?status=success&msg=FamiliarCreado");
            exit();
            break;

        case 'PUT':
            if (isset($_POST['id'])) {
                // Aquí invocarías la actualización en tu FamiliarService
                // $servicio->actualizar($_POST);
                header("Location: ../../../index.html?status=success&msg=FamiliarActualizado");
                exit();
            } else {
                throw new Exception("Falta el ID para actualizar el familiar.");
            }
            break;

        case 'DELETE':
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            if ($id !== null) {
                $servicio->eliminar((int)$id);
                header("Location: ../../../index.html?status=success&msg=FamiliarEliminado");
                exit();
            } else {
                throw new Exception("Falta el ID para eliminar.");
            }
            break;

        default:
            throw new Exception("Método no soportado.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo "<h1>Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
