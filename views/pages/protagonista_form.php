<?php
// views/pages/protagonista_form.php
//
// Alta de un protagonista (?id_seccion=) o edición de sus datos (?id=).
// El DNI solo se edita en el alta; en edición se muestra como solo lectura.
// La patrulla se ofrece en un select si la rama tiene pequeño equipo.

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\ProtagonistaController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => $app->obtener(ProtagonistaController::class)->paginaFormulario($_GET));
$seccion = $datos['seccion'];
$ficha = $datos['ficha'];
$esEdicion = $ficha !== null;
$persona = $ficha?->persona;
$protagonista = $ficha?->protagonista;
$equipo = $seccion->pequenioEquipo();

$tituloPagina = $esEdicion
    ? 'Editar a ' . $persona->nombre . ' ' . $persona->apellido
    : 'Nuevo protagonista';
$claseRama = Vista::claseRama($seccion->rama->id);
$contenedorAngosto = true;
require __DIR__ . '/../includes/cabecera.php';
?>
<div class="barra-superior-inst mb-4">
    <h1 class="h3 fw-bold mb-1"><?= $esEdicion ? 'Editar datos' : 'Nuevo protagonista' ?></h1>
    <p class="mb-0">
        <a class="text-reset" href="<?= e(url('views/pages/perfil_seccion.php?id=' . $seccion->seccion->id)) ?>"><?= e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) ?></a>
        · <?= e($seccion->rama->titulo) ?>
    </p>
</div>

<section class="tarjeta-blanca-borde p-4">
    <form data-accion="protagonista.guardar">
        <?= Csrf::campo() ?>
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id" value="<?= (int) $protagonista->id ?>">
        <?php else: ?>
            <input type="hidden" name="id_seccion" value="<?= (int) $seccion->seccion->id ?>">
        <?php endif; ?>

        <h2 class="h5 texto-violeta fw-bold mb-3">Datos personales</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="dni" class="form-label">DNI</label>
                <?php if ($esEdicion): ?>
                    <input type="text" class="form-control" id="dni" value="<?= e(number_format($persona->dni, 0, ',', '.')) ?>" readonly>
                    <input type="hidden" name="dni" value="<?= (int) $persona->dni ?>">
                <?php else: ?>
                    <input type="text" class="form-control" id="dni" name="dni" inputmode="numeric" maxlength="12" required placeholder="Ej.: 12.345.678">
                <?php endif; ?>
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

        <h2 class="h5 texto-violeta fw-bold mb-3">En la sección</h2>
        <div class="row g-3 mb-4">
            <?php if ($equipo !== null): ?>
                <div class="col-md-6">
                    <label for="id_patrulla" class="form-label"><?= e($equipo) ?> <span class="text-muted">(opcional)</span></label>
                    <select class="form-select" id="id_patrulla" name="id_patrulla">
                        <option value="">Ninguna</option>
                        <?php foreach ($datos['patrullas'] as $p): ?>
                            <option value="<?= (int) $p->id ?>" <?= ($protagonista?->idPatrulla === $p->id) ? 'selected' : '' ?>><?= e($p->nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label for="anio_ingreso" class="form-label">Año de ingreso <span class="text-muted">(opcional)</span></label>
                <input type="text" class="form-control" id="anio_ingreso" name="anio_ingreso" inputmode="numeric" maxlength="4" placeholder="Ej.: <?= date('Y') ?>" value="<?= e((string) ($protagonista?->anioIngreso ?? '')) ?>"><?php // En el alta $protagonista es null: ?-> evita el warning que se imprimía dentro del campo. ?>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="promesa" name="promesa" value="1" <?= ($protagonista?->promesa ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="promesa">Hizo la Promesa</label>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="boton-primario px-4 py-2 fw-bold">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i><?= $esEdicion ? 'Guardar cambios' : 'Dar de alta' ?>
            </button>
            <?php if ($esEdicion): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $protagonista->id)) ?>">Cancelar</a>
            <?php else: ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('views/pages/protagonistas.php?id_seccion=' . $seccion->seccion->id)) ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/pie.php'; ?>
