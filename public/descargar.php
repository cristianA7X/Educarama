<?php
// public/descargar.php
//
// Descarga (o muestra, si es una imagen y viene &ver=1) un documento:
//   descargar.php?id=7          descargar.php?id=7&ver=1
// Es GET y no pasa por api.php porque responde un archivo, no JSON. Pasa por
// Pagina::cargar(), que emite el archivo y convierte los errores en páginas:
// sin sesión va al login; sin acceso o id inexistente, 404; archivo que falta
// en el disco, 404 "El archivo no está disponible.".

declare(strict_types=1);

$app = require_once __DIR__ . '/../src/main.php';

use Educarama\Controller\DocumentoController;
use Educarama\Controller\Pagina;

Pagina::cargar(fn () => $app->obtener(DocumentoController::class)->descargar($_GET));
