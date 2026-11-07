<?php
// views/includes/menu.php
//
// El menú lateral (drawer). Lo incluye views/includes/cabecera.php, que antes
// carga $datosMenu con MenuController.
//
// Es contextual: Inicio, cada grupo del usuario con sus secciones (cada una
// con sus links según el rol que tiene) y, al final, su cuenta. Un link que
// el usuario no puede usar no se muestra.
//
// Por debajo de 992 px es un cajón que se abre encima del contenido; desde
// 992 px está a la vista y se oculta con el botón de la barra superior
// (public/js/educarama.js).

declare(strict_types=1);

use Educarama\Helper\Vista;
use Educarama\Model\Enum\NivelRol;

$usuarioMenu = $datosMenu['usuario'];
$urlActual = $_SERVER['REQUEST_URI'] ?? '';

// El link de la página actual se marca; ?id=3&inactivos=1 sigue siendo ?id=3.
$esActivo = static function (string $ruta) use ($urlActual): bool {
    $url = url($ruta);
    return $url === $urlActual || str_starts_with($urlActual, $url . '&');
};

/** Un link del menú. @param string $ruta Relativa al proyecto, ej. 'views/pages/grupo.php?id=1' */
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
?>
<nav class="sidebar offcanvas offcanvas-start" id="menuLateral" tabindex="-1" aria-label="Menú principal">
    <div class="offcanvas-header d-lg-none">
        <span class="fw-bold texto-violeta">Menú</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar el menú"></button>
    </div>

    <div class="offcanvas-body">
        <p class="menu-usuario mb-2"><i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= e($usuarioMenu->nombre) ?></p>

        <div class="nav">
            <?php $enlaceMenu('views/pages/inicio.php', 'bi-grid', 'Inicio'); ?>

            <?php foreach ($datosMenu['grupos'] as $grupoMenu): ?>
                <?php
                $idGrupo = (int) $grupoMenu->grupo->id;
                ?>
                <p class="menu-titulo">Grupo <?= e($grupoMenu->grupo->codigo) ?> · <?= e($grupoMenu->grupo->nombre) ?></p>

                <?php
                $enlaceMenu("views/pages/grupo.php?id=$idGrupo", 'bi-house-door', 'Resumen del grupo');
                if ($grupoMenu->tieneRolDeGrupo()) {
                    $enlaceMenu("views/pages/eventos.php?id_grupo=$idGrupo", 'bi-megaphone', 'Eventos del grupo');
                }
                if ($grupoMenu->nivel === NivelRol::Jefe) {
                    $enlaceMenu("views/pages/accesos.php?id_grupo=$idGrupo", 'bi-key', 'Accesos del grupo');
                }
                ?>

                <?php foreach ($grupoMenu->secciones as $seccionMenu): ?>
                    <?php
                    // Una sección que el usuario ve solo por su rol de grupo no tiene páginas propias para él.
                    if ($seccionMenu->nivel === null) {
                        continue;
                    }

                    $idSeccion = (int) $seccionMenu->seccion->id;
                    $enlaces = [["views/pages/perfil_seccion.php?id=$idSeccion", 'bi-info-circle', 'Perfil de la sección']];
                    if ($seccionMenu->puedeVerDetalle()) {
                        $enlaces[] = ["views/pages/protagonistas.php?id_seccion=$idSeccion", 'bi-people', 'Protagonistas'];
                        if ($seccionMenu->pequenioEquipo() !== null) {
                            $enlaces[] = ["views/pages/patrullas.php?id_seccion=$idSeccion", 'bi-shield-shaded', $seccionMenu->pequenioEquipo() . 's'];
                        }
                    }
                    $enlaces[] = ["views/pages/eventos.php?id_seccion=$idSeccion", 'bi-megaphone', 'Eventos'];
                    if ($seccionMenu->puedeEliminar()) {
                        $enlaces[] = ["views/pages/accesos.php?id_seccion=$idSeccion", 'bi-key', 'Accesos'];
                    }

                    // Se despliega sola la sección donde está el usuario.
                    $desplegada = array_filter($enlaces, fn (array $e) => $esActivo($e[0])) !== [];
                    $idColapso = "menu-seccion-$idSeccion";
                    ?>
                    <a class="nav-link" href="#<?= e($idColapso) ?>" data-bs-toggle="collapse" role="button"
                       aria-expanded="<?= $desplegada ? 'true' : 'false' ?>" aria-controls="<?= e($idColapso) ?>">
                        <span class="punto-rama <?= e(Vista::claseRama($seccionMenu->rama->id)) ?>" aria-hidden="true"></span>
                        <span><?= e($seccionMenu->seccion->nombre) ?></span>
                        <i class="bi bi-chevron-down caret" aria-hidden="true"></i>
                    </a>
                    <div class="sub-menu collapse<?= $desplegada ? ' show' : '' ?>" id="<?= e($idColapso) ?>">
                        <?php foreach ($enlaces as [$ruta, $icono, $texto]) { $enlaceMenu($ruta, $icono, $texto); } ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="menu-separador"></div>

            <?php
            $enlaceMenu('views/pages/mi_cuenta.php', 'bi-person-gear', 'Mi cuenta');
            $enlaceMenu('views/pages/solicitud_alta.php', 'bi-plus-circle', 'Solicitar el alta de un grupo');
            if ($usuarioMenu->esAdmin) {
                $enlaceMenu('views/pages/solicitudes.php', 'bi-clipboard-check', 'Solicitudes de alta');
            }
            ?>

            <button type="button" class="nav-link" data-accion="usuario.cerrarSesion"
                    data-confirmar="¿Cerrar sesión?" data-texto="Se cerrará tu sesión actual." data-boton="Sí, cerrar sesión">
                <span class="icon"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
                <span>Cerrar sesión</span>
            </button>
        </div>
    </div>
</nav>
