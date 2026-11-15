<?php
// public/descargar.php
declare(strict_types=1);

$app = require_once __DIR__ . '/../src/main.php';

use Educarama\Controller\DocumentoController;

// Obtiene el controlador con sus dependencias inyectadas y emite el archivo
$app->obtener(DocumentoController::class)->descargar($_GET)->emitir();