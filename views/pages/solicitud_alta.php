<?php
// views/pages/solicitud_alta.php
//
// Pedir el alta de un grupo nuevo. Un administrador la revisa: si la aprueba se
// crean el grupo y sus secciones, y quien la envió queda como Jefe del grupo.
// Abajo, las solicitudes que ya envió (con el motivo si se rechazó alguna).
// Con una pendiente no se puede enviar otra, así que el formulario se oculta.

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\SolicitudAltaController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;
use Educarama\Model\Enum\EstadoSolicitud;

$datos = Pagina::cargar(fn () => (new SolicitudAltaController())->paginaSolicitud());

$tituloPagina = 'Solicitar el alta de un grupo';
$scriptsPagina = ['public/js/solicitud_alta.js'];
require __DIR__ . '/../includes/cabecera.php';

/** Los <option> del select de ramas (se usa en la fila de ejemplo y en el template). */
$opcionesRamas = '<option value="">Elegí la rama…</option>';
foreach ($datos['ramas'] as $rama) {
    $opcionesRamas .= '<option value="' . (int) $rama->id . '">' . e($rama->titulo) . '</option>';
}
?>
<div class="barra-superior-inst mb-4">
    <h1 class="h3 fw-bold mb-1">Solicitar el alta de un grupo</h1>
    <p class="mb-0">Un administrador la revisa. Si se aprueba, quedás como Jefe del grupo.</p>
</div>

<?php if ($datos['hayPendiente']): ?>
    <div class="alert alert-warning">
        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
        Ya tenés una solicitud pendiente: esperá a que la revisen para enviar otra.
    </div>
<?php else: ?>
    <section class="tarjeta-blanca-borde p-4 mb-4">
        <form data-accion="solicitud_alta.enviar" id="formSolicitud">
            <?= Csrf::campo() ?>

            <h2 class="h5 texto-violeta fw-bold">El grupo</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="codigo" class="form-label">Número del grupo</label>
                    <input type="text" class="form-control" id="codigo" name="codigo" maxlength="20" required>
                </div>
                <div class="col-md-8">
                    <label for="nombre" class="form-label">Nombre del grupo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" placeholder="Ej: Los Aromos" required>
                </div>
            </div>

            <h2 class="h5 texto-violeta fw-bold">Las secciones</h2>
            <p class="text-muted small">Una por cada Manada, Unidad, Caminantes o Rovers del grupo.</p>

            <div id="filasSecciones" class="d-grid gap-2 mb-3">
                <div class="row g-2 align-items-end fila-seccion">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nombre de la sección</label>
                        <input type="text" class="form-control" name="seccion_nombre[]" maxlength="100" placeholder="Ej: Luna Llena" required>
                    </div>
                    <div class="col-9 col-md-5">
                        <label class="form-label">Rama</label>
                        <select class="form-select" name="seccion_rama[]" required><?= $opcionesRamas ?></select>
                    </div>
                    <div class="col-3 col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger" data-quitar-seccion aria-label="Quitar esta sección">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <template id="plantillaSeccion">
                <div class="row g-2 align-items-end fila-seccion">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nombre de la sección</label>
                        <input type="text" class="form-control" name="seccion_nombre[]" maxlength="100" placeholder="Ej: Luna Llena" required>
                    </div>
                    <div class="col-9 col-md-5">
                        <label class="form-label">Rama</label>
                        <select class="form-select" name="seccion_rama[]" required><?= $opcionesRamas ?></select>
                    </div>
                    <div class="col-3 col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger" data-quitar-seccion aria-label="Quitar esta sección">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </template>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" id="agregarSeccion">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Agregar otra sección
                </button>
                <button type="submit" class="boton-primario px-4 py-2 fw-bold">Enviar la solicitud</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<h2 class="home-section-title">Mis solicitudes</h2>
<?php if ($datos['solicitudes'] === []): ?>
    <p class="text-muted">Todavía no enviaste ninguna solicitud.</p>
<?php else: ?>
    <div class="d-grid gap-3">
        <?php foreach ($datos['solicitudes'] as $s): ?>
            <article class="tarjeta-blanca-borde p-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <h3 class="h6 fw-bold mb-0">
                        <?= e(Vista::nombreCompletoGrupo($s->solicitud->datosGrupo['nombre'])) ?>
                    </h3>
                    <span class="badge text-bg-<?= e(Vista::colorEstado($s->solicitud->estado)) ?>"><?= e($s->solicitud->estado->etiqueta()) ?></span>
                </div>
                <p class="small text-muted mb-2">Enviada el <?= e(Vista::fechaDeFechaHora($s->solicitud->fecha)) ?></p>
                <ul class="mb-0">
                    <?php foreach ($s->secciones as $seccion): ?>
                        <li><?= e(Vista::nombreCompletoSeccion($seccion['nombre'], $seccion['id_rama'])) ?> <span class="text-muted">· <?= e($seccion['rama']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($s->solicitud->estado === EstadoSolicitud::Rechazada): ?>
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>Motivo del rechazo:</strong> <?= e($s->solicitud->motivoRechazo ?? 'Sin motivo.') ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/pie.php'; ?>
