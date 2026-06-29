<?php
// backend/Infraestructura/Controladores/RamaController.php

header("Access-Control-Allow-Origin: *");
// Cambiamos el tipo de contenido porque ahora respondemos HTML o redireccionamos
header("Content-Type: text/html; charset=UTF-8"); 

require_once __DIR__ . '/../Datos/RamaRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/RamaService.php';

$metodo = $_SERVER['REQUEST_METHOD'];

// Truco para soportar PUT y DELETE desde formularios HTML estándar
if ($metodo === 'POST' && isset($_POST['_method'])) {
    $metodo = strtoupper($_POST['_method']);
}

$repo = new RamaRepository();
$servicio = new RamaService($repo);

try {
    switch ($metodo) {
        case 'GET':
            if (isset($_GET['id'])) {
                $resultado = $servicio->obtenerPorId((int)$_GET['id']);
            } else {
                $resultado = $servicio->listarTodas();
            }
            // Podés redirigir a una página o imprimir el resultado
            echo "<h1>Listado procesado con éxito</h1>";
            break;

        case 'POST':
            
            if (isset($_POST['nombre']) && isset($_POST['id_grupo']) && isset($_POST['id_rama'])) {
                $servicio->crear($_POST['nombre'], (int)$_POST['id_grupo'], (int)$_POST['id_rama']);
                
                header("Location: ../../../index.html?status=success&msg=Creado");
                exit();
            } else {
                throw new Exception("Faltan datos para crear la rama.");
            }
            break;

        case 'PUT':
            if (isset($_POST['id']) && isset($_POST['nombre']) && isset($_POST['id_grupo']) && isset($_POST['id_rama'])) {
                $servicio->actualizar((int)$_POST['id'], $_POST['nombre'], (int)$_POST['id_grupo'], (int)$_POST['id_rama']);
                
                header("Location: ../../../index.html?status=success&msg=Actualizado");
                exit();
            } else {
                throw new Exception("Faltan datos para actualizar la rama.");
            }
            break;

        case 'DELETE':
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            
            if ($id !== null) {
                $servicio->eliminar((int)$id);
                header("Location: ../../../index.html?status=success&msg=Eliminado");
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
    echo "<h1>Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
