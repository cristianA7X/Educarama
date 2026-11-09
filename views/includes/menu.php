<?php
// views/includes/menu.php
//
// El menú lateral (drawer). Lo incluye views/includes/cabecera.php, que antes
// carga $datosMenu con MenuController.
//
// Items: Inicio, Grupos (dropdown con los grupos del usuario, sus Eventos y
// Roles si tiene permiso), Secciones y Protagonistas (links directos; las views
// generales todavía no existen, apuntan al inicio con fragmento). El filtro por
// grupo/sección/rama va en cada view, no en el drawer.
//
// Por debajo de 992 px es un cajón que se abre encima del contenido; desde
// 992 px está a la vista y se oculta con el botón de la barra superior
// (public/js/educarama.js).

declare(strict_types=1);

use Educarama\Helper\Vista;
use Educarama\Model\Enum\NivelRol;

$usuarioMenu = $datosMenu['usuario'];
$urlActual = $_SERVER['REQUEST_URI'] ?? '';

$esActivo = static function (string $ruta) use ($urlActual): bool {
    $url = url($ruta);
    return $url === $urlActual || str_starts_with($urlActual, $url . '&');
};

/** Un link del menú. */
$enlaceMenu = static function (string $ruta, string $icono, string $texto) use ($esActivo): void {
    $activo = $esActivo($ruta);
    printf(
        '<a class="nav-link%s" href="%s"%s><span class="icon"><i class="bi %s" aria-hidden="true"></i></span><span>%s</span></a>',
        $activo ? ' active' : '',
        e(url($ruta)),
        $activo ? ' aria-current="page"' : '',
        e($icono),
        e($texto),
    );
};

// Armar los links del dropdown de Grupos.
// Por cada grupo: su página, Eventos (si tiene rol de grupo) y Roles (si es Jefe).
$linksGrupos = [];

foreach ($datosMenu['grupos'] as $grupoMenu) {
    $idGrupo = (int) $grupoMenu->grupo->id;
    $linksGrupos[] = ["views/pages/grupo.php?id=$idGrupo", 'bi-house-door', Vista::nombreCompletoGrupo($grupoMenu->grupo->nombre)];
}

// Eventos grupales: vista general filtrada a los de todo el grupo.
if ($linksGrupos !== []) {
    $linksGrupos[] = ['views/pages/eventos.php?grupales=1', 'bi-megaphone', 'Eventos grupales'];
}

$rutaSecciones = 'views/pages/secciones.php';
$rutaProtagonistas = 'views/pages/protagonistas.php';
$rutaEventos = 'views/pages/eventos.php';
?>
<nav class="sidebar offcanvas offcanvas-start" id="menuLateral" tabindex="-1" aria-label="Menú principal">
    <div class="offcanvas-header d-lg-none">
        <span class="fw-bold texto-violeta">Menú</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar el menú"></button>
    </div>

    <div class="offcanvas-body">
        <p class="menu-usuario mb-2"><i class="bi bi-person-circle me-1"
                aria-hidden="true"></i><?= e($usuarioMenu->nombre) ?></p>

        <div class="nav">
            <?php $enlaceMenu('views/pages/inicio.php', 'bi-grid', 'Inicio'); ?>

            <?php if ($linksGrupos !== []): ?>
                <?php
                // Grupos: dropdown si hay más de un link, directo si hay uno solo.
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
                data-texto="Se cerrará tu sesión actual." data-boton="Sí, cerrar sesión">
                <span class="icon"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
                <span>Cerrar sesión</span>
            </button>
        </div>
    </div>
</nav>