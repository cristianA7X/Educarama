<?php
// views/pages/perfil_seccion.php
declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\SeccionController;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => $app->obtener(SeccionController::class)->paginaPerfil($_GET));

$seccion = $datos['seccion'];
$conteos = $datos['conteos'];
$proximos = $datos['proximos'];
$equipo = $seccion->pequenioEquipo();

$tituloPagina = Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id);
$claseRama = Vista::claseRama($seccion->rama->id);

require_once __DIR__ . '/../includes/cabecera.php';
?>
<!-- Resto de tu HTML se mantiene igual -->
<section class="barra-superior-inst mb-4">
    <h1 class="fw-bold mb-1"><?= e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) ?></h1>
    <p class="mb-0">
        <?= e($seccion->rama->titulo) ?>
        · <a class="text-reset" href="<?= e(url('views/pages/grupo.php?id=' . $seccion->grupo->id)) ?>"><?= e(Vista::nombreCompletoGrupo($seccion->grupo->nombre)) ?></a>
        <?php if ($seccion->nivel !== null): ?>
            <span class="badge text-bg-secondary ms-1"><?= e($seccion->nivel->etiqueta()) ?></span>
        <?php endif; ?>
    </p>
</section>

<!-- Conteos -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="tarjeta-blanca-borde p-3 text-center h-100">
            <p class="h2 texto-violeta fw-bold mb-1"><?= $conteos->protagonistas ?></p>
            <p class="small text-muted mb-0">Protagonistas</p>
        </div>
    </div>
    <?php if ($equipo !== null): ?>
        <div class="col-6 col-md-3">
            <div class="tarjeta-blanca-borde p-3 text-center h-100">
                <p class="h2 texto-violeta fw-bold mb-1"><?= count($conteos->porPatrulla) ?></p>
                <p class="small text-muted mb-0"><?= e($equipo) ?>s</p>
            </div>
        </div>
        <?php if ($conteos->sinPatrulla > 0): ?>
            <div class="col-6 col-md-3">
                <div class="tarjeta-blanca-borde p-3 text-center h-100">
                    <p class="h2 texto-violeta fw-bold mb-1"><?= $conteos->sinPatrulla ?></p>
                    <p class="small text-muted mb-0">Sin <?= e(mb_strtolower($equipo)) ?></p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Links rápidos -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php if ($seccion->puedeVerDetalle()): ?>
        <a class="boton-primario px-3 py-2 fw-bold text-decoration-none" href="<?= e(url('views/pages/protagonistas.php?id_seccion=' . $seccion->seccion->id)) ?>">
            <i class="bi bi-people me-1" aria-hidden="true"></i>Protagonistas
        </a>
        <?php if ($equipo !== null): ?>
            <a class="btn btn-outline-secondary" href="<?= e(url('views/pages/patrullas.php?id_seccion=' . $seccion->seccion->id)) ?>">
                <img class="icono-oficial me-1" src="<?= e(Vista::iconoOficial('banderin')) ?>" alt=""><?= e($equipo) ?>s
            </a>
        <?php endif; ?>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= e(url('views/pages/eventos.php?id_seccion=' . $seccion->seccion->id)) ?>">
        <img class="icono-oficial me-1" src="<?= e(Vista::iconoOficial('campamento')) ?>" alt="">Eventos
    </a>
    <?php if ($seccion->puedeEliminar()): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('views/pages/accesos.php?id_seccion=' . $seccion->seccion->id)) ?>">
            <i class="bi bi-person-badge me-1" aria-hidden="true"></i>Roles
        </a>
    <?php endif; ?>
</div>

<?php if ($equipo !== null && $conteos->porPatrulla !== []): ?>
    <!-- Distribución por patrulla -->
    <section class="tarjeta-blanca-borde p-4 mb-4">
        <h2 class="h5 texto-violeta fw-bold mb-3"><?= e($equipo) ?>s</h2>
        <div class="list-group">
            <?php foreach ($conteos->porPatrulla as $fila): ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                   href="<?= e(url('views/pages/perfil_patrulla.php?id=' . $fila['patrulla']->id)) ?>">
                    <?= e($fila['patrulla']->nombre) ?>
                    <span class="badge text-bg-secondary"><?= $fila['cantidad'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Próximos eventos -->
<section class="tarjeta-blanca-borde p-4">
    <h2 class="h5 texto-violeta fw-bold mb-3">Próximos eventos</h2>
    <?php if ($proximos === []): ?>
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No hay eventos próximos.</p>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($proximos as $proximo): ?>
                <?php $evento = $proximo->evento; ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <strong><?= e($evento->titulo) ?></strong>
                        <span class="badge text-bg-<?= e(Vista::colorPrioridad($evento->prioridad)) ?>"><?= e($evento->prioridad->etiqueta()) ?></span>
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i><?= e(Vista::periodo($evento->fechaInicio, $evento->fechaFin)) ?>
                        · <?= $proximo->esDeTodoElGrupo() ? 'Todo el grupo' : 'Algunas secciones' ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/pie.php'; ?>
