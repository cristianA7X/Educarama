<?php
// views/pages/perfil_patrulla.php
//
// Perfil de una patrulla/seisena/equipo (?id=): nombre (renombrable), sus
// integrantes (quitar desde acá), agregar integrantes de la sección y eliminar
// la patrulla (solo Jefe).

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\PatrullaController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => $app->obtener(PatrullaController::class)->paginaPerfil($_GET));
$patrulla = $datos['patrulla'];
$seccion = $datos['seccion'];
$integrantes = $datos['integrantes'];
$candidatos = $datos['candidatos'];
$equipo = $seccion->pequenioEquipo() ?? 'Patrulla';
$equipoMin = mb_strtolower($equipo);
$idPatrulla = (int) $patrulla->id;

$tituloPagina = $patrulla->nombre;
$claseRama = Vista::claseRama($seccion->rama->id);
require __DIR__ . '/../includes/cabecera.php';
?>
<section class="barra-superior-inst mb-3 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1 class="fw-bold mb-1"><?= e($patrulla->nombre) ?></h1>
        <p class="mb-0">
            <?= e($equipo) ?>
            · <a class="text-reset" href="<?= e(url('views/pages/perfil_seccion.php?id=' . $seccion->seccion->id)) ?>"><?= e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) ?></a>
            · <?= e($seccion->rama->titulo) ?>
        </p>
    </div>
    <?php if ($seccion->puedeEditar() || $seccion->puedeEliminar()): ?>
        <div class="dropdown">
            <button class="btn btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Más acciones">
                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if ($seccion->puedeEditar()): ?>
                    <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalRenombrar"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Renombrar</button></li>
                <?php endif; ?>
                <?php if ($seccion->puedeEliminar()): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item text-danger" type="button" data-accion="patrulla.eliminar"
                                data-datos='<?= e(json_encode(['id_patrulla' => $idPatrulla])) ?>'
                                data-confirmar="¿Eliminar «<?= e($patrulla->nombre) ?>»?"
                                data-texto="Sus integrantes quedan sin <?= e($equipoMin) ?>. No se puede deshacer."
                                data-boton="Sí, eliminar" data-peligro>
                            <i class="bi bi-trash me-2" aria-hidden="true"></i>Eliminar
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>

<!-- Integrantes -->
<section class="tarjeta-blanca-borde p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="h5 texto-violeta fw-bold mb-0">Integrantes <span class="badge text-bg-secondary"><?= count($integrantes) ?></span></h2>
        <?php if ($seccion->puedeEditar() && $candidatos !== []): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Agregar
            </button>
        <?php endif; ?>
    </div>

    <?php if ($integrantes === []): ?>
        <p class="text-muted mb-0">Sin integrantes todavía.</p>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($integrantes as $ficha): ?>
                <?php $nombreProt = $ficha->persona->nombre . ' ' . $ficha->persona->apellido; ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <a class="text-decoration-none text-reset fw-bold" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $ficha->protagonista->id)) ?>">
                        <?= e($nombreProt) ?>
                    </a>
                    <?php if ($seccion->puedeEditar()): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-accion="patrulla.quitarIntegrante"
                                data-datos='<?= e(json_encode(['id_patrulla' => $idPatrulla, 'id_protagonista' => (int) $ficha->protagonista->id])) ?>'
                                data-confirmar="¿Quitar a <?= e($nombreProt) ?> de <?= e($patrulla->nombre) ?>?" data-boton="Sí, quitar" data-peligro data-recargar>
                            <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Quitar a <?= e($nombreProt) ?></span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($seccion->puedeEditar()): ?>
    <!-- Renombrar -->
    <div class="modal fade" id="modalRenombrar" tabindex="-1" aria-labelledby="tituloRenombrar" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" data-accion="patrulla.renombrar" data-recargar>
                <?= Csrf::campo() ?>
                <input type="hidden" name="id_patrulla" value="<?= $idPatrulla ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="tituloRenombrar">Renombrar <?= e($equipoMin) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="nuevoNombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nuevoNombre" name="nombre" maxlength="100" required value="<?= e($patrulla->nombre) ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="boton-primario px-3 py-2 fw-bold">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($candidatos !== []): ?>
        <!-- Agregar integrante -->
        <div class="modal fade" id="modalAgregar" tabindex="-1" aria-labelledby="tituloAgregar" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" data-accion="patrulla.asignarIntegrante" data-recargar>
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id_patrulla" value="<?= $idPatrulla ?>">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="tituloAgregar">Agregar integrante</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <label for="idProtagonista" class="form-label">Protagonista</label>
                        <select class="form-select" id="idProtagonista" name="id_protagonista" required>
                            <option value="">Elegí un protagonista…</option>
                            <?php foreach ($candidatos as $c): ?>
                                <?php $nota = $c->nombrePatrulla !== null ? ' (en ' . $c->nombrePatrulla . ')' : ''; ?>
                                <option value="<?= (int) $c->protagonista->id ?>"><?= e($c->persona->apellido . ', ' . $c->persona->nombre . $nota) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Si ya está en otra <?= e($equipoMin) ?>, se pasa a esta.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="boton-primario px-3 py-2 fw-bold">Agregar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/pie.php'; ?>
