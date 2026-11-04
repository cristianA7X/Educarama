<?php
// views/includes/error.php
//
// Página de error que muestra Educarama\Controller\Pagina (403, 404, 500...).
// Recibe $codigo (int) y $mensaje (string, ya apto para el usuario).
// Versión mínima: el diseño se hace junto con las Views.

declare(strict_types=1);

$titulos = [403 => 'Sin permiso', 404 => 'No encontrado', 500 => 'Error'];
$titulo  = $titulos[$codigo] ?? 'No se pudo completar';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?> · Educarama</title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <p><?= htmlspecialchars($mensaje) ?></p>
        <p><a href="<?= htmlspecialchars(url('views/pages/inicio.php')) ?>">Volver al inicio</a></p>
    </main>
</body>
</html>
