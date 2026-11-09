<?php
// views/pages/patrullas.php
//
// Listado de patrullas (seisenas/equipos) de una sección (?id_seccion=), con
// sus integrantes y los protagonistas sin patrulla. Crear patrulla desde acá;
// renombrar y eliminar desde el perfil de cada una.

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\PatrullaController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => (new PatrullaController())->paginaListado($_GET));
$seccion = $datos['seccion'];
$patrullas = $datos['patrullas'];
$integrantes = $datos['integrantes'];
$sinPatrulla = $datos['sinPatrulla'];
$equipo = $seccion->pequenioEquipo() ?? 'Patrulla';
$equipoMin = mb_strtolower($equipo);

$tituloPagina = $equipo . 's · ' . Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id);
$claseRama = Vista::claseRama($seccion->rama->id);
require __DIR__ . '/../includes/cabecera.php';
?>
<section class="barra-superior-inst mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1 class="fw-bold mb-1"><?= e($equipo) ?>s</h1>
        <p class="mb-0">
            <a class="text-reset" href="<?= e(url('views/pages/perfil_seccion.php?id=' . $seccion->seccion->id)) ?>"><?= e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) ?></a>
            · <?= e($seccion->rama->titulo) ?>
        </p>
    </div>
    <?php if ($seccion->puedeEditar()): ?>
        <button type="button" class="boton-primario px-3 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalCrear">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Crear <?= e($equipoMin) ?>
        </button>
    <?php endif; ?>
</section>

<?php if ($patrullas === [] && $sinPatrulla === []): ?>
    <section class="tarjeta-blanca-borde p-4">
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No hay <?= e($equipoMin) ?>s ni protagonistas en esta sección.</p>
    </section>
<?php endif; ?>

<?php foreach ($patrullas as $p): ?>
    <?php $miembros = $integrantes[(int) $p->id] ?? []; ?>
    <section class="tarjeta-blanca-borde p-4 mb-3 <?= e($claseRama) ?>">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h5 texto-violeta fw-bold mb-0">
                <a class="text-reset text-decoration-none" href="<?= e(url('views/pages/perfil_patrulla.php?id=' . $p->id)) ?>">
                    <?= e($p->nombre) ?>
                </a>
                <span class="badge text-bg-secondary ms-1"><?= count($miembros) ?></span>
            </h2>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('views/pages/perfil_patrulla.php?id=' . $p->id)) ?>">
                Ver <?= e($equipoMin) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <?php if ($miembros === []): ?>
            <p class="text-muted mb-0">Sin integrantes.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($miembros as $ficha): ?>
                    <a class="list-group-item list-group-item-action" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $ficha->protagonista->id)) ?>">
                        <?= e($ficha->persona->nombre . ' ' . $ficha->persona->apellido) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php if ($sinPatrulla !== []): ?>
    <section class="tarjeta-blanca-borde p-4 mb-3">
        <h2 class="h5 fw-bold mb-3 text-muted">Sin <?= e($equipoMin) ?></h2>
        <div class="list-group">
            <?php foreach ($sinPatrulla as $ficha): ?>
                <a class="list-group-item list-group-item-action" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $ficha->protagonista->id)) ?>">
                    <?= e($ficha->persona->nombre . ' ' . $ficha->persona->apellido) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($seccion->puedeEditar()): ?>
    <!-- Crear patrulla -->
    <div class="modal fade" id="modalCrear" tabindex="-1" aria-labelledby="tituloCrear" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" data-accion="patrulla.crear">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id_seccion" value="<?= (int) $seccion->seccion->id ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="tituloCrear">Crear <?= e($equipoMin) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="nombrePatrulla" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombrePatrulla" name="nombre" maxlength="100" required placeholder="Ej.: <?= e($equipo) ?> Gris">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="boton-primario px-3 py-2 fw-bold">Crear</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/pie.php'; ?>
