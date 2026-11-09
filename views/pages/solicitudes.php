<?php
// views/pages/solicitudes.php
//
// Revisión de solicitudes de alta (solo admin). Muestra las solicitudes
// pendientes de la más vieja a la más nueva, con quién las envió, qué
// grupo y secciones pidieron, y botones para aprobar o rechazar con motivo.

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\SolicitudAltaController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => (new SolicitudAltaController())->paginaRevision());
$pendientes = $datos['pendientes'];

$tituloPagina = 'Solicitudes de alta';
require __DIR__ . '/../includes/cabecera.php';
?>
<section class="barra-superior-inst mb-4">
    <h1 class="fw-bold mb-1">Solicitudes de alta</h1>
    <p class="mb-0">Solicitudes pendientes de revisión</p>
</section>

<?php if ($pendientes === []): ?>
    <section class="tarjeta-blanca-borde p-4">
        <p class="text-muted mb-0"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>No hay solicitudes pendientes.</p>
    </section>
<?php else: ?>
    <div class="d-grid gap-4">
        <?php foreach ($pendientes as $ep): ?>
            <?php
            $sol = $ep->solicitud;
            $grupo = $sol->datosGrupo;
            ?>
            <div class="tarjeta-blanca-borde p-4">
                <!-- Encabezado: grupo pedido y quién lo pidió -->
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">
                            <?= e(Vista::nombreCompletoGrupo($grupo['nombre'] ?? '')) ?>
                            <span class="badge text-bg-secondary ms-1"><?= e($grupo['codigo'] ?? '') ?></span>
                        </h2>
                        <p class="small text-muted mb-0">
                            <i class="bi bi-person me-1" aria-hidden="true"></i><?= e($ep->nombreSolicitante) ?>
                            · <a href="mailto:<?= e($ep->emailSolicitante) ?>"><?= e($ep->emailSolicitante) ?></a>
                            <?php if ($sol->fecha !== null): ?>
                                · <i class="bi bi-calendar-event me-1" aria-hidden="true"></i><?= e(Vista::fecha($sol->fecha)) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Secciones pedidas -->
                <h3 class="h6 fw-bold mb-2">Secciones</h3>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($ep->secciones as $sec): ?>
                        <li class="list-group-item px-0 py-1"><?= e(Vista::nombreCompletoSeccion($sec['nombre'], $sec['id_rama'])) ?> · <span class="text-muted"><?= e($sec['rama']) ?></span></li>
                    <?php endforeach; ?>
                </ul>

                <!-- Acciones: aprobar y rechazar -->
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="boton-primario px-3 py-2 fw-bold" data-accion="solicitud_alta.aprobar"
                            data-datos='<?= e(json_encode(['id_solicitud' => (int) $sol->id])) ?>'
                            data-confirmar="¿Aprobar la solicitud de «<?= e(Vista::nombreCompletoGrupo($grupo['nombre'] ?? '')) ?>»?"
                            data-texto="Se creará el grupo con sus secciones y <?= e($ep->nombreSolicitante) ?> quedará como Jefe."
                            data-boton="Sí, aprobar">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Aprobar
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRechazo-<?= (int) $sol->id ?>">
                        <i class="bi bi-x-lg me-1" aria-hidden="true"></i>Rechazar
                    </button>
                </div>
            </div>

            <!-- Modal de rechazo: pide el motivo -->
            <div class="modal fade" id="modalRechazo-<?= (int) $sol->id ?>" tabindex="-1" aria-labelledby="tituloRechazo-<?= (int) $sol->id ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <form class="modal-content" data-accion="solicitud_alta.rechazar">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id_solicitud" value="<?= (int) $sol->id ?>">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="tituloRechazo-<?= (int) $sol->id ?>">Rechazar solicitud</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">Grupo: <strong><?= e(Vista::nombreCompletoGrupo($grupo['nombre'] ?? '')) ?></strong></p>
                            <label for="motivo-<?= (int) $sol->id ?>" class="form-label">Motivo del rechazo</label>
                            <textarea class="form-control" id="motivo-<?= (int) $sol->id ?>" name="motivo" rows="3" required placeholder="Explicá por qué se rechaza. El solicitante va a ver este mensaje."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger fw-bold">Rechazar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/pie.php'; ?>
