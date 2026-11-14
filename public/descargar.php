<?php
// public/descargar.php
//
// Descarga (o muestra, si es una imagen y viene &ver=1) un documento:
//   descargar.php?id=7          descargar.php?id=7&ver=1
// Es GET y no pasa por api.php porque responde un archivo, no JSON. Los
// errores (sin sesión, sin acceso, archivo faltante) se muestran como una página.

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use Educarama\Controller\DocumentoController;
use Educarama\Controller\Pagina;

Pagina::cargar(fn () => (new DocumentoController())->descargar($_GET));
