<?php
// views/pages/accesos.php
//
// Gestión de roles de un grupo (?id_grupo=) o de una sección (?id_seccion=).
// Cada fila muestra nombre, email y rol, con un select para cambiarlo y un
// botón para quitarlo (si el Service lo permite). Abajo, un formulario para
// agregar a alguien por email.

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\AccesoController;
use Educarama\Controller\Pagina;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => $app->obtener(AccesoController::class)->paginaListado($_GET));
$modo = $datos['modo'];
$pantalla = $datos['pantalla'];

if ($modo === 'grupo') {
    $grupoPantalla = $datos['grupo'];
    $tituloCtx = Vista::nombreCompletoGrupo($grupoPantalla->grupo->nombre);
    $linkVolver = 'views/pages/grupo.php?id=' . $grupoPantalla->grupo->id;
    $ambitoHidden = '<input type="hidden" name="ambito" value="grupo"><input type="hidden" name="id_grupo" value="' . (int) $grupoPantalla->grupo->id . '">';
} else {
    $seccion = $datos['seccion'];
    $tituloCtx = Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id) . ' · ' . $seccion->rama->titulo;
    $linkVolver = 'views/pages/perfil_seccion.php?id=' . $seccion->seccion->id;
    $ambitoHidden = '<input type="hidden" name="ambito" value="seccion"><input type="hidden" name="id_seccion" value="' . (int) $seccion->seccion->id . '">';
    $claseRama = Vista::claseRama($seccion->rama->id);
}

$tituloPagina = 'Roles · ' . ($modo === 'grupo' ? Vista::nombreCompletoGrupo($grupoPantalla->grupo->nombre) : Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id));
require __DIR__ . '/../includes/cabecera.php';
?>
<section class="barra-superior-inst mb-4">
    <h1 class="fw-bold mb-1">Roles</h1>
    <p class="mb-0"><a class="text-reset" href="<?= e(url($linkVolver)) ?>"><?= e($tituloCtx) ?></a></p>
</section>

<section class="tarjeta-blanca-borde p-4 mb-4">
    <?php if ($pantalla->accesos === []): ?>
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No hay usuarios con acceso.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pantalla->accesos as $fila): ?>
                        <?php $u = $fila->usuario; ?>
                        <tr>
                            <td class="fw-bold"><?= e($u->nombre) ?></td>
                            <td><?= e($u->email) ?></td>
                            <td>
                                <?php if ($fila->puedeCambiarRol()): ?>
                                    <form class="d-inline" data-accion="acceso.cambiarRol" data-recargar>
                                        <?= Csrf::campo() ?>
                                        <?= $ambitoHidden ?>
                                        <input type="hidden" name="id_usuario" value="<?= $u->idUsuario ?>">
                                        <select class="form-select form-select-sm d-inline-block w-auto" name="rol" onchange="this.form.requestSubmit()">
                                            <?php foreach ($fila->rolesAsignables as $r): ?>
                                                <option value="<?= $r->value ?>" <?= $u->nivel === $r ? 'selected' : '' ?>><?= e($r->etiqueta()) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary"><?= e($u->nivel->etiqueta()) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($fila->puedeQuitar): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-accion="acceso.quitar"
                                            data-datos='<?= e(json_encode(array_merge(['id_usuario' => $u->idUsuario, 'ambito' => $modo === 'grupo' ? 'grupo' : 'seccion'], $modo === 'grupo' ? ['id_grupo' => (int) $grupoPantalla->grupo->id] : ['id_seccion' => (int) $seccion->seccion->id]))) ?>'
                                            data-confirmar="¿Quitar el acceso de <?= e($u->nombre) ?>?" data-boton="Sí, quitar" data-peligro data-recargar>
                                        <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Quitar</span>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
// Desplegable de miembros por sección (solo en modo grupo).
// Muestra quién tiene acceso a cada sección y con qué rol, de solo lectura.
// Para editar hay que ir a la sección.
if ($modo === 'grupo' && !empty($datos['accesosSecciones'])):
?>
    <section class="tarjeta-blanca-borde p-4 mb-4">
        <h2 class="h5 texto-violeta fw-bold mb-3">Miembros por sección</h2>
        <p class="small text-muted mb-3">Para editar roles de una sección, entrá a sus Roles.</p>
        <div class="accordion" id="acordeonSecciones">
            <?php foreach ($datos['accesosSecciones'] as $i => $item): ?>
                <?php
                $sec = $item['seccion'];
                $accSec = $item['accesos'];
                $collapseId = 'seccion-' . (int) $sec->id;
                ?>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                            <?= e($sec->nombre) ?>
                            <span class="badge text-bg-secondary ms-2"><?= count($accSec) ?></span>
                        </button>
                    </h3>
                    <div id="<?= $collapseId ?>" class="accordion-collapse collapse" data-bs-parent="#acordeonSecciones">
                        <div class="accordion-body p-0">
                            <?php if ($accSec === []): ?>
                                <p class="text-muted p-3 mb-0">Sin usuarios asignados directamente.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Nombre</th>
                                                <th>Email</th>
                                                <th>Rol</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($accSec as $uSec): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold"><?= e($uSec->nombre) ?></td>
                                                    <td><?= e($uSec->email) ?></td>
                                                    <td><span class="badge text-bg-secondary"><?= e($uSec->nivel->etiqueta()) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <div class="p-3 pt-0">
                                <a class="btn btn-outline-secondary btn-sm mt-2" href="<?= e(url('views/pages/accesos.php?id_seccion=' . (int) $sec->id)) ?>">
                                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar roles de <?= e($sec->nombre) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($pantalla->puedeAgregar()): ?>
    <section class="tarjeta-blanca-borde p-4">
        <h2 class="h5 texto-violeta fw-bold mb-3">Agregar a alguien</h2>
        <p class="small text-muted mb-3">Buscá por email una cuenta ya registrada. Si no tiene cuenta, pedile que se registre primero.</p>
        <form data-accion="acceso.dar" class="row g-2 align-items-end" data-recargar>
            <?= Csrf::campo() ?>
            <?= $ambitoHidden ?>
            <div class="col-md-5">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="nombre@ejemplo.com">
            </div>
            <div class="col-md-4">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" id="rol" name="rol" required>
                    <?php foreach ($pantalla->rolesParaAgregar as $r): ?>
                        <option value="<?= $r->value ?>"><?= e($r->etiqueta()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="boton-primario px-3 py-2 fw-bold w-100">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Agregar
                </button>
            </div>
        </form>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/pie.php'; ?>
