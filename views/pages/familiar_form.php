<?php
// views/pages/evento_form.php
declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\EventoController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;
use Educarama\Model\Enum\Prioridad;

$datos = Pagina::cargar(fn () => $app->obtener(EventoController::class)->paginaFormulario($_GET));

$opciones = $datos['opciones'];
$eventoConSec = $datos['evento'];
$esEdicion = $eventoConSec !== null;
$evento = $eventoConSec?->evento;
$idSecciones = $eventoConSec?->idSecciones ?? [];
$grupo = $opciones->grupo;

$alcanceActual = $esEdicion
    ? ($eventoConSec->esDeTodoElGrupo() ? 'grupo' : 'secciones')
    : ($opciones->puedeTodoElGrupo ? 'grupo' : 'secciones');

$tituloPagina = $esEdicion ? 'Editar evento' : 'Nuevo evento';
$contenedorAngosto = true;

require_once __DIR__ . '/../includes/cabecera.php';
?>
<!-- Resto de tu HTML se mantiene igual -->
<div class="barra-superior-inst mb-4">
    <h1 class="h3 fw-bold mb-1"><?= $esEdicion ? 'Editar familiar' : 'Agregar familiar' ?></h1>
    <p class="mb-0">
        De <a class="text-reset fw-bold" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $idProtagonista)) ?>"><?= e($nombreProt) ?></a>
        · <a class="text-reset" href="<?= e(url('views/pages/perfil_seccion.php?id=' . $seccion->seccion->id)) ?>"><?= e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) ?></a>
    </p>
</div>

<?php if ($esEdicion && $datos['compartido']): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
        Este familiar está vinculado a otros protagonistas. Los cambios en sus datos se ven en todos.
    </div>
<?php endif; ?>

<section class="tarjeta-blanca-borde p-4">
    <form data-accion="familiar.guardar">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id_protagonista" value="<?= $idProtagonista ?>">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id_familiar" value="<?= (int) $familiar->id ?>">
        <?php endif; ?>

        <h2 class="h5 texto-violeta fw-bold mb-3">Datos personales</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="dni" class="form-label">DNI</label>
                <input type="text" class="form-control" id="dni" name="dni" inputmode="numeric" maxlength="12" required placeholder="Ej.: 12.345.678" value="<?= $esEdicion ? e(number_format($persona->dni, 0, ',', '.')) : '' ?>">
            </div>
            <div class="col-md-4">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" required value="<?= e($persona->nombre ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" class="form-control" id="apellido" name="apellido" maxlength="100" required value="<?= e($persona->apellido ?? '') ?>">
            </div>
            <div class="col-md-8 col-lg-6">
                <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento <span class="text-muted">(opcional)</span></label>
                <?php // Tres listas en vez del calendario del navegador (ver Vista::campoFecha). ?>
                <?= Vista::campoFecha('fecha_nacimiento', 'fecha_nacimiento', $persona?->fechaNacimiento) ?>
            </div>
        </div>

        <h2 class="h5 texto-violeta fw-bold mb-3">Contacto</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="telefono" class="form-label">Teléfono <span class="text-muted">(opcional)</span></label>
                <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="50" value="<?= e($familiar->telefonoContacto ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email <span class="text-muted">(opcional)</span></label>
                <input type="email" class="form-control" id="email" name="email" maxlength="200" value="<?= e($familiar->emailContacto ?? '') ?>">
            </div>
        </div>

        <h2 class="h5 texto-violeta fw-bold mb-3">Vínculo con <?= e($prot->persona->nombre) ?></h2>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="vinculo" class="form-label">Relación <span class="text-muted">(opcional)</span></label>
                <input type="text" class="form-control" id="vinculo" name="vinculo" maxlength="50" placeholder="Ej.: Madre, Tío, Tutor" value="<?= e($vinculo->vinculo ?? '') ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="emergencia" name="emergencia" value="1" <?= ($vinculo?->esContactoDeEmergencia ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="emergencia">
                        <i class="bi bi-telephone-plus me-1" aria-hidden="true"></i>Contacto de emergencia
                    </label>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="boton-primario px-4 py-2 fw-bold">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i><?= $esEdicion ? 'Guardar cambios' : 'Agregar familiar' ?>
            </button>
            <a class="btn btn-outline-secondary" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $idProtagonista)) ?>">Cancelar</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/pie.php'; ?>
