<?php
// backend/Infraestructura/Controladores/ProtagonistaController.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8"); 

require_once __DIR__ . '/../Datos/ProtagonistaRepository.php';
require_once __DIR__ . '/../Datos/PersonaRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/ProtagonistaService.php';

$metodo = $_SERVER['REQUEST_METHOD'];

// Captura de métodos PUT y DELETE desde formularios estándar
if ($metodo === 'POST' && isset($_POST['_method'])) {
    $metodo = strtoupper($_POST['_method']);
}

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
            echo "<h1>Búsqueda procesada con éxito</h1>";
            // Aquí puedes volcar o renderizar el $resultado
            break;

        case 'POST':
            if (isset($_POST['accion']) && $_POST['accion'] === 'asignar_rama') {
                if (isset($_POST['id_protagonista']) && isset($_POST['id_rama'])) {
                    $servicio->asignarRama((int)$_POST['id_protagonista'], (int)$_POST['id_rama']);
                    header("Location: ../../../index.html?status=success&msg=RamaAsignada");
                    exit();
                } else {
                    throw new Exception("Faltan id_protagonista o id_rama.");
                }
            } else {
                $servicio->crear($_POST);
                header("Location: ../../../index.html?status=success&msg=ProtagonistaCreado");
                exit();
            }
            break;

        case 'PUT':
            // Se asume que $servicio->actualizar() procesa los datos modificados
            if (isset($_POST['id'])) {
                // Aquí ejecutas el método de tu servicio para actualizar, por ejemplo:
                // $servicio->actualizar($_POST);
                header("Location: ../../../index.html?status=success&msg=ProtagonistaActualizado");
                exit();
            } else {
                throw new Exception("Falta el ID para actualizar.");
            }
            break;

        case 'DELETE':
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            if ($id !== null) {
                $servicio->eliminar((int)$id);
                header("Location: ../../../index.html?status=success&msg=ProtagonistaEliminado");
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
