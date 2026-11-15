<?php
// views/pages/eventos.php
declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\EventoController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

// --- Modo general (sin parámetros) ---
if (!isset($_GET['id_grupo']) && !isset($_GET['id_seccion'])) {
    $datos = Pagina::cargar(fn () => $app->obtener(EventoController::class)->paginaGeneral());
    $generales = $datos['generales'];
    $soloGrupales = isset($_GET['grupales']);
    
    $tituloPagina = $soloGrupales ? 'Eventos grupales' : 'Eventos';
    require_once __DIR__ . '/../includes/cabecera.php';
    ?>
    <!-- ... tu HTML de modo general ... -->
    <?php
    require __DIR__ . '/../includes/pie.php';
    return;
}

// --- Modo grupo / sección (con parámetros) ---
$datos = Pagina::cargar(fn () => $app->obtener(EventoController::class)->paginaListado($_GET));

$modo = $datos['modo'];
$pantalla = $datos['pantalla'];
$eventos = $pantalla->eventos;
$nombres = $pantalla->nombresSecciones;

if ($modo === 'grupo') {
    $grupoPantalla = $datos['grupo'];
    $tituloCtx = Vista::nombreCompletoGrupo($grupoPantalla->grupo->nombre);
    $linkVolver = 'views/pages/grupo.php?id=' . $grupoPantalla->grupo->id;
    $idGrupo = (int) $grupoPantalla->grupo->id;
    $rutaActual = 'views/pages/eventos.php?id_grupo=' . $idGrupo;
} else {
    $seccion = $datos['seccion'];
    $tituloCtx = Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id) . ' · ' . $seccion->rama->titulo;
    $linkVolver = 'views/pages/perfil_seccion.php?id=' . $seccion->seccion->id;
    $idGrupo = (int) $seccion->grupo->id;
    $rutaActual = 'views/pages/eventos.php?id_seccion=' . $seccion->seccion->id;
    $claseRama = Vista::claseRama($seccion->rama->id);
}

$tituloPagina = 'Eventos · ' . ($modo === 'grupo' ? Vista::nombreCompletoGrupo($grupoPantalla->grupo->nombre) : Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id));

require_once __DIR__ . '/../includes/cabecera.php';
?>
<!-- Resto de tu HTML de modo listado se mantiene igual -->
<section class="barra-superior-inst mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
<!-- ... -->
<section class="barra-superior-inst mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1 class="fw-bold mb-1">Eventos</h1>
        <p class="mb-0"><a class="text-reset" href="<?= e(url($linkVolver)) ?>"><?= e($tituloCtx) ?></a></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($pantalla->puedeVerInactivos && !$pantalla->incluyeInactivos): ?>
            <a class="boton-primario px-3 py-2 fw-bold text-decoration-none" href="<?= e(url($rutaActual . '&inactivos=1')) ?>">
                <i class="bi bi-eye-slash me-1" aria-hidden="true"></i>Ver inactivos
            </a>
        <?php elseif ($pantalla->incluyeInactivos): ?>
            <a class="boton-primario px-3 py-2 fw-bold text-decoration-none" href="<?= e(url($rutaActual)) ?>">
                <i class="bi bi-eye me-1" aria-hidden="true"></i>Solo activos
            </a>
        <?php endif; ?>
        <?php if ($pantalla->puedeCrear): ?>
            <a class="boton-primario px-3 py-2 fw-bold text-decoration-none" href="<?= e(url('views/pages/evento_form.php?id_grupo=' . $idGrupo)) ?>">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo evento
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if ($eventos === []): ?>
    <section class="tarjeta-blanca-borde p-4">
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No hay eventos<?= $pantalla->incluyeInactivos ? '' : ' activos' ?>.</p>
    </section>
<?php else: ?>
    <div class="d-grid gap-3">
        <?php foreach ($eventos as $ep): ?>
            <?php
            $ev = $ep->evento->evento;
            $esDeTodo = $ep->evento->esDeTodoElGrupo();
            $activo = $ev->activo;
            ?>
            <div class="tarjeta-blanca-borde p-4<?= !$activo ? ' opacity-75' : '' ?>">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <h2 class="h5 fw-bold mb-1">
                            <?= e($ev->titulo) ?>
                            <?php if (!$activo): ?>
                                <span class="badge text-bg-secondary ms-1">Inactivo</span>
                            <?php endif; ?>
                        </h2>
                        <p class="small text-muted mb-0">
                            <i class="bi bi-calendar-event me-1" aria-hidden="true"></i><?= e(Vista::periodo($ev->fechaInicio, $ev->fechaFin)) ?>
                            · <span class="badge text-bg-<?= e(Vista::colorPrioridad($ev->prioridad)) ?>"><?= e($ev->prioridad->etiqueta()) ?></span>
                            <?php if ($ev->esPublico): ?>
                                · <i class="bi bi-globe2 me-1" aria-hidden="true"></i>Público
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($ep->puedeEditar || $ep->puedeEliminar): ?>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones">
                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($ep->puedeEditar): ?>
                                    <li><a class="dropdown-item" href="<?= e(url('views/pages/evento_form.php?id=' . $ev->id)) ?>"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Editar</a></li>
                                    <?php if ($activo): ?>
                                        <li>
                                            <button class="dropdown-item" type="button" data-accion="evento.desactivar"
                                                    data-datos='<?= e(json_encode(['id_evento' => (int) $ev->id, 'volver' => $rutaActual])) ?>'
                                                    data-confirmar="¿Desactivar «<?= e($ev->titulo) ?>»?" data-texto="Ya no aparece en los listados." data-boton="Sí, desactivar">
                                                <i class="bi bi-pause-circle me-2" aria-hidden="true"></i>Desactivar
                                            </button>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <button class="dropdown-item" type="button" data-accion="evento.reactivar"
                                                    data-datos='<?= e(json_encode(['id_evento' => (int) $ev->id, 'volver' => $rutaActual])) ?>'
                                                    data-confirmar="¿Reactivar «<?= e($ev->titulo) ?>»?" data-boton="Sí, reactivar">
                                                <i class="bi bi-play-circle me-2" aria-hidden="true"></i>Reactivar
                                            </button>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($ep->puedeEliminar): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button class="dropdown-item text-danger" type="button" data-accion="evento.eliminar"
                                                data-datos='<?= e(json_encode(['id_evento' => (int) $ev->id, 'volver' => $rutaActual])) ?>'
                                                data-confirmar="¿Eliminar «<?= e($ev->titulo) ?>»?" data-texto="No se puede deshacer." data-boton="Sí, eliminar" data-peligro>
                                            <i class="bi bi-trash me-2" aria-hidden="true"></i>Eliminar
                                        </button>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="mb-2"><?= nl2br(e($ev->descripcion)) ?></p>
                <p class="small text-muted mb-0">
                    <?php if ($esDeTodo): ?>
                        <i class="bi bi-people me-1" aria-hidden="true"></i>Todo el grupo
                    <?php else: ?>
                        <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>
                        <?= e(implode(', ', array_map(fn (int $id) => $nombres[$id] ?? "Sección $id", $ep->evento->idSecciones))) ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/pie.php'; ?>
