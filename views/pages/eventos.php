<?php
// views/pages/eventos.php
//
// Tres modos:
//   Sin parámetros          → vista general: todos los eventos de los grupos accesibles,
//                             en tabla compacta con filtro por grupo.
//   ?id_grupo=              → eventos del grupo (rol de grupo necesario).
//   ?id_seccion=            → eventos de la sección (acceso a la sección necesario).
// &inactivos=1 muestra también los desactivados (Responsable en adelante).

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Controller\EventoController;
use Educarama\Controller\Pagina;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

// ─── Modo general (sin parámetros) ─────────────────────────────────────
if (!isset($_GET['id_grupo']) && !isset($_GET['id_seccion'])) {
    $datos = Pagina::cargar(fn () => (new EventoController())->paginaGeneral());
    $generales = $datos['generales'];
    $soloGrupales = isset($_GET['grupales']);

    $tituloPagina = $soloGrupales ? 'Eventos grupales' : 'Eventos';
    require __DIR__ . '/../includes/cabecera.php';
    ?>
    <section class="barra-superior-inst mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="fw-bold mb-1"><?= $soloGrupales ? 'Eventos grupales' : 'Eventos' ?></h1>
            <p class="mb-0"><?= $soloGrupales ? 'Eventos de todo el grupo, de todos tus grupos.' : 'Todos los eventos de tus grupos y secciones.' ?></p>
        </div>
    </section>

    <?php
    // Contar eventos visibles (si solo grupales, filtrar los que no son de todo el grupo).
    $totalVisible = 0;
    if ($soloGrupales) {
        foreach ($generales->porGrupo as $fila) {
            foreach ($fila['pantalla']->eventos as $ep) {
                if ($ep->evento->esDeTodoElGrupo()) {
                    $totalVisible++;
                }
            }
        }
    } else {
        $totalVisible = $generales->total();
    }
    ?>
    <?php if ($totalVisible === 0): ?>
        <section class="tarjeta-blanca-borde p-4">
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No hay eventos<?= $soloGrupales ? ' grupales' : '' ?>.</p>
        </section>
    <?php else: ?>
        <?php // Filtro por grupo (solo si hay más de uno)
        if (count($generales->porGrupo) > 1): ?>
            <div class="mb-3">
                <select id="filtro-grupo" class="form-select form-select-sm w-auto">
                    <option value="">Todos los grupos</option>
                    <?php foreach ($generales->porGrupo as $fila): ?>
                        <option value="<?= (int) $fila['grupo']->grupo->id ?>">
                            <?= e($fila['grupo']->grupo->nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-eventos">
                <thead class="table-light">
                    <tr>
                        <th>Evento</th>
                        <th style="min-width:120px">Fecha</th>
                        <th>Prioridad</th>
                        <th>Alcance</th>
                        <th>Grupo</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($generales->porGrupo as $fila):
                        $grupoPantalla = $fila['grupo'];
                        $pantalla = $fila['pantalla'];
                        $idGrupoFila = (int) $grupoPantalla->grupo->id;
                        $nombres = $pantalla->nombresSecciones;
                        $grupoLabel = $grupoPantalla->grupo->nombre;

                        foreach ($pantalla->eventos as $ep):
                            $esDeTodo = $ep->evento->esDeTodoElGrupo();
                            if ($soloGrupales && !$esDeTodo) { continue; }
                            $ev = $ep->evento->evento;
                            $alcance = $esDeTodo
                                ? 'Todo el grupo'
                                : implode(', ', array_map(fn (int $id) => $nombres[$id] ?? "Sección $id", $ep->evento->idSecciones));
                    ?>
                        <tr data-grupo="<?= $idGrupoFila ?>"<?= !$ev->activo ? ' class="opacity-75"' : '' ?>>
                            <td>
                                <strong><?= e($ev->titulo) ?></strong>
                                <?php if (!$ev->activo): ?>
                                    <span class="badge text-bg-secondary ms-1">Inactivo</span>
                                <?php endif; ?>
                                <?php if ($ev->esPublico): ?>
                                    <i class="bi bi-globe2 ms-1 text-muted" title="Público" aria-hidden="true"></i>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= e(Vista::periodo($ev->fechaInicio, $ev->fechaFin)) ?></td>
                            <td><span class="badge text-bg-<?= e(Vista::colorPrioridad($ev->prioridad)) ?>"><?= e($ev->prioridad->etiqueta()) ?></span></td>
                            <td class="small">
                                <?php if ($esDeTodo): ?>
                                    <i class="bi bi-people me-1" aria-hidden="true"></i><?= e($alcance) ?>
                                <?php else: ?>
                                    <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i><?= e($alcance) ?>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= e($grupoLabel) ?></td>
                            <td class="text-end">
                                <?php if ($ep->puedeEditar || $ep->puedeEliminar): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones">
                                            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php if ($ep->puedeEditar): ?>
                                                <li><a class="dropdown-item" href="<?= e(url('views/pages/evento_form.php?id=' . $ev->id)) ?>"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Editar</a></li>
                                            <?php endif; ?>
                                            <?php if ($ep->puedeEliminar): ?>
                                                <li>
                                                    <button class="dropdown-item text-danger" type="button" data-accion="evento.eliminar"
                                                            data-datos='<?= e(json_encode(['id_evento' => (int) $ev->id, 'volver' => 'views/pages/eventos.php'])) ?>'
                                                            data-confirmar="¿Eliminar «<?= e($ev->titulo) ?>»?" data-texto="No se puede deshacer." data-boton="Sí, eliminar" data-peligro>
                                                        <i class="bi bi-trash me-2" aria-hidden="true"></i>Eliminar
                                                    </button>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var filtro = document.getElementById('filtro-grupo');
            if (!filtro) return;

            filtro.addEventListener('change', function () {
                var valor = this.value;
                var filas = document.querySelectorAll('#tabla-eventos tbody tr');
                filas.forEach(function (fila) {
                    fila.style.display = (!valor || fila.dataset.grupo === valor) ? '' : 'none';
                });
            });
        });
        </script>
    <?php endif; ?>
    <?php
    require __DIR__ . '/../includes/pie.php';
    return;
}

// ─── Modo grupo / sección (con parámetros) ─────────────────────────────
$datos = Pagina::cargar(fn () => (new EventoController())->paginaListado($_GET));
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
require __DIR__ . '/../includes/cabecera.php';
?>
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
