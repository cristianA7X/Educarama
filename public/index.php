<?php
// public/index.php
//
// Puerta de entrada del sitio: manda a la página de inicio. Si no hay sesión,
// esa página redirige al login y, después de entrar, vuelve acá.

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Location: ' . url('views/pages/inicio.php'), true, 302);
exit;
