<?php
$tituloPagina = "Gestionar avisos";

require_once '../../backend/Infraestructura/Datos/Database.php';

$conexion = Database::getConexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $prioridad = $_POST['prioridad'];

    $stmt = $conexion->prepare("
        INSERT INTO avisos 
        (titulo, descripcion, fecha_inicio, fecha_fin, prioridad, activo)
        VALUES 
        (:titulo, :descripcion, :fecha_inicio, :fecha_fin, :prioridad, 1)
    ");

    $stmt->execute([
        ':titulo' => $titulo,
        ':descripcion' => $descripcion,
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin,
        ':prioridad' => $prioridad
    ]);

    header("Location: gestionar_avisos.php");
    exit;
}

$stmtAvisos = $conexion->prepare("
    SELECT *
    FROM avisos
    ORDER BY fecha_inicio DESC
");

$stmtAvisos->execute();
$avisos = $stmtAvisos->fetchAll();
?>