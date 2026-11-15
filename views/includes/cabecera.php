<?php
// views/includes/cabecera.php
declare(strict_types=1);

use Educarama\Controller\MenuController;
use Educarama\Controller\Pagina;
use Educarama\Helper\Csrf;
use Educarama\Helper\Flash;

$tituloPagina = $tituloPagina ?? 'Educarama';
$claseRama = $claseRama ?? '';
$sinMenu = $sinMenu ?? false;
$contenedorAngosto = $contenedorAngosto ?? false;

// Aseguramos que los mensajes flash estén inicializados como array
$mensajesFlash = Flash::tomar() ?? [];

// El resto de tu cabecera...

// El menú se pide ANTES de mostrar nada: sin sesión, redirige al login.
$datosMenu = $sinMenu ? null : Pagina::cargar(fn () => $app->obtener(MenuController::class)->datosMenu());

// Agregar la fecha del archivo a la URL hace que el navegador recargue el CSS y el JS cuando cambian.
$version = static fn (string $ruta): int => (int) @filemtime(__DIR__ . '/../../' . $ruta);

$clasesAlerta = ['exito' => 'success', 'error' => 'danger', 'aviso' => 'warning'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <meta name="url-api" content="<?= e(url('public/api.php')) ?>">
    <meta name="url-login" content="<?= e(url('views/pages/login.php')) ?>">
    <title><?= e($tituloPagina) ?> · Educarama</title>

    <!-- Si el menú estaba oculto la última vez, se oculta antes de dibujar la página (evita el parpadeo). -->
    <script>
        try { if (localStorage.getItem('educarama.menu') === 'oculto') document.documentElement.classList.add('menu-oculto'); } catch (e) {}
    </script>

    <!-- Favicon: el logo de Educarama (nunca una marca oficial de Scouts de Argentina). -->
    <link rel="icon" type="image/png" href="<?= e(url('views/assets/images/Logos/Logos_Educarama/LogoBaseV4.png')) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(url('public/css/styles.css')) ?>?v=<?= $version('public/css/styles.css') ?>">
</head>

<body class="<?= e(trim($claseRama . ($sinMenu ? ' no-sidebar' : ''))) ?>">
    <a class="visually-hidden-focusable position-absolute p-2 bg-white" style="z-index: 2000" href="#contenido">Saltar al contenido</a>

    <header class="header-inst">
        <?php if (!$sinMenu): ?>
            <button type="button" class="boton-menu" id="botonMenu" aria-controls="menuLateral" aria-expanded="false" aria-label="Abrir o cerrar el menú">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
        <?php endif; ?>
        <a class="marca" href="<?= e(url('views/pages/inicio.php')) ?>">
            <img class="marca-logo rounded-circle" src="<?= e(url('views/assets/images/Logos/Logos_Educarama/LogoBaseV4.png')) ?>" alt="">
            <p class="marca-titulo">Educarama<span class="d-none d-sm-inline"> - <?= e($tituloPagina) ?></span></p>
        </a>
    </header>

    <?php if (!$sinMenu) { require __DIR__ . '/menu.php'; } ?>

    <div class="contenido-pagina">
        <main class="main-content-wrapper" id="contenido">
            <div class="container contenedor-principal p-4<?= $contenedorAngosto ? ' contenedor-angosto' : '' ?>">

                <?php foreach ($mensajesFlash as $mensajeFlash): ?>
                    <div class="alert alert-<?= e($clasesAlerta[$mensajeFlash['tipo']] ?? 'info') ?> alert-dismissible fade show" role="alert">
                        <?= e($mensajeFlash['mensaje']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endforeach; ?>
