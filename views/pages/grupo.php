<?php
// views/pages/grupo.php
//
// Pantalla del grupo (?id=): las secciones que ve el usuario (tarjetas con el
// color de la rama) y los próximos eventos del grupo.

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\SeccionController;
use Educarama\Helper\Vista;
use Educarama\Model\Enum\NivelRol;

$datos = Pagina::cargar(fn () => (new SeccionController())->paginaGrupo($_GET));
$grupoPantalla = $datos['grupo'];
$proximos = $datos['proximos'];
$grupo = $grupoPantalla->grupo;

$tituloPagina = Vista::nombreCompletoGrupo($grupo->nombre);
require __DIR__ . '/../includes/cabecera.php';
?>
<section class="barra-superior-inst mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1 class="fw-bold mb-1"><?= e(Vista::nombreCompletoGrupo($grupo->nombre)) ?></h1>
        <?php if ($grupoPantalla->nivel !== null): ?>
            <p class="mb-0"><span class="badge text-bg-secondary"><?= e($grupoPantalla->nivel->etiqueta()) ?></span></p>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($grupoPantalla->tieneRolDeGrupo()): ?>
            <a class="boton-primario px-3 py-2 fw-bold text-decoration-none" href="<?= e(url('views/pages/eventos.php?id_grupo=' . $grupo->id)) ?>">
                <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Eventos
            </a>
        <?php endif; ?>
        <?php if ($grupoPantalla->nivel === NivelRol::Jefe): ?>
            <a class="boton-primario px-3 py-2 fw-bold text-decoration-none" href="<?= e(url('views/pages/accesos.php?id_grupo=' . $grupo->id)) ?>">
                <i class="bi bi-person-badge me-1" aria-hidden="true"></i>Roles
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Secciones -->
<h2 class="h5 texto-violeta fw-bold mb-3">Secciones</h2>
<div class="row g-3 mb-4">
    <?php foreach ($grupoPantalla->secciones as $seccion): ?>
        <?php
        // El logo oficial de la rama, o el ícono de Bootstrap si la rama no tiene uno.
        $logoRama = Vista::logoRama($seccion->rama->id);
        $marcaRama = $logoRama !== null
            ? '<div class="rama-icono con-logo"><img src="' . e($logoRama) . '" alt=""></div>'
            : '<div class="rama-icono"><i class="bi ' . e(Vista::iconoRama($seccion->rama->id)) . '" aria-hidden="true"></i></div>';

        $contenido = $marcaRama
            . '<div><h3>' . e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) . '</h3>'
            . '<p>' . e($seccion->rama->titulo) . ($seccion->nivel !== null ? ' · ' . e($seccion->nivel->etiqueta()) : '') . '</p></div>';
        ?>
        <div class="col-12 col-md-6">
            <?php if ($seccion->nivel !== null): ?>
                <a class="rama-card text-decoration-none <?= e(Vista::claseRama($seccion->rama->id)) ?>"
                   href="<?= e(url('views/pages/perfil_seccion.php?id=' . $seccion->seccion->id)) ?>">
                    <?= $contenido ?>
                </a>
            <?php else: ?>
                <div class="rama-card <?= e(Vista::claseRama($seccion->rama->id)) ?> opacity-75">
                    <?= $contenido ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Próximos eventos -->
<h2 class="h5 texto-violeta fw-bold mb-3">Próximos eventos</h2>
<?php if ($proximos === []): ?>
    <p class="text-muted">No hay eventos próximos.</p>
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
<?php require __DIR__ . '/../includes/pie.php'; ?>
