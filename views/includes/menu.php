<?php
// views/includes/menu.php
declare(strict_types=1);

use Educarama\Controller\Pagina;
use Educarama\Controller\MenuController;

// Si $app ya está definido en la página principal, lo usamos; si no, lo requerimos.
if (!isset($app)) {
    $app = require_once __DIR__ . '/../../src/main.php';
}

$datosMenu = Pagina::cargar(fn () => $app->obtener(MenuController::class)->datosMenu());
$usuarioMenu = $datosMenu['usuario'];
$grupos = $datosMenu['grupos'];

// Helpers internos para renderizar los links del menú limpiamente
$esActivo = static fn (string $ruta): bool => str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', $ruta);

$enlaceMenu = static function (string $ruta, string $icono, string $texto) use ($esActivo): void {
    $act = $esActivo($ruta) ? ' active' : '';
    printf(
        '<a class="nav-link%s" href="%s"><span class="icon"><i class="bi %s" aria-hidden="true"></i></span><span>%s</span></a>',
        $act,
        e(url($ruta)),
        $icono,
        e($texto)
    );
};

// Preparar enlaces de grupos
$linksGrupos = [];
foreach ($grupos as $g) {
    $linksGrupos[] = ['views/pages/grupo.php?id=' . (int) $g->grupo->id, 'bi-house-door', $g->grupo->nombre];
}

$rutaEventos = 'views/pages/eventos.php';
$rutaSecciones = 'views/pages/secciones.php';
$rutaProtagonistas = 'views/pages/protagonistas.php';
?>

<!-- HTML del drawer lateral -->
<nav class="sidebar offcanvas offcanvas-start" id="menuLateral" tabindex="-1" aria-label="Menú principal">
    <div class="offcanvas-header d-lg-none">
        <span class="fw-bold texto-violeta">Menú</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar el menú"></button>
    </div>
    <div class="offcanvas-body">
        <p class="menu-usuario mb-2"><i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= e($usuarioMenu->nombre) ?></p>
        <div class="nav">
            <?php $enlaceMenu('views/pages/inicio.php', 'bi-grid', 'Inicio'); ?>
            <?php if ($linksGrupos !== []): ?>
                <?php
                if (count($linksGrupos) === 1) {
                    $enlaceMenu($linksGrupos[0][0], 'bi-house-door', 'Grupos');
                } else {
                    $desplegada = array_filter($linksGrupos, fn(array $e) => $esActivo($e[0])) !== [];
                    ?>
                    <a class="nav-link" href="#menu-grupos" data-bs-toggle="collapse" role="button"
                        aria-expanded="<?= $desplegada ? 'true' : 'false' ?>" aria-controls="menu-grupos">
                        <span class="icon"><i class="bi bi-house-door" aria-hidden="true"></i></span>
                        <span>Grupos</span>
                        <i class="bi bi-chevron-down caret" aria-hidden="true"></i>
                    </a>
                    <div class="sub-menu collapse<?= $desplegada ? ' show' : '' ?>" id="menu-grupos">
                        <?php foreach ($linksGrupos as [$ruta, $icono, $texto]) {
                            $enlaceMenu($ruta, $icono, $texto);
                        } ?>
                    </div>
                <?php } ?>
            <?php endif; ?>
            <?php $enlaceMenu($rutaEventos, 'bi-megaphone', 'Eventos'); ?>
            <?php $enlaceMenu($rutaSecciones, 'bi-flag', 'Secciones'); ?>
            <?php $enlaceMenu($rutaProtagonistas, 'bi-people', 'Protagonistas'); ?>
            <div class="menu-separador"></div>
            <?php
            $enlaceMenu('views/pages/mi_cuenta.php', 'bi-person-gear', 'Mi cuenta');
            $enlaceMenu('views/pages/solicitud_alta.php', 'bi-plus-circle', 'Solicitar el alta de un grupo');
            if ($usuarioMenu->esAdmin) {
                $enlaceMenu('views/pages/solicitudes.php', 'bi-clipboard-check', 'Solicitudes de alta');
            }
            ?>
            <button type="button" class="nav-link" data-accion="usuario.cerrarSesion" data-confirmar="¿Cerrar sesión?"
                data-texto="Se cerrará tu sesión actual." data-boton="Sí, cerrar sesión" data-peligro>
                <span class="icon"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
                <span>Cerrar sesión</span>
            </button>
        </div>
    </div>
</nav>