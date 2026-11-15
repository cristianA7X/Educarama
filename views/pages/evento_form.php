<?php
// views/pages/evento_form.php
//
// Alta de un evento (?id_grupo=) o edición (?id=). El alcance se elige
// explícito: "Todo el grupo" o "Algunas secciones" con al menos una.

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\EventoController;
use Educarama\Controller\Pagina;
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
require __DIR__ . '/../includes/cabecera.php';
?>
<div class="barra-superior-inst mb-4">
    <h1 class="h3 fw-bold mb-1"><?= $esEdicion ? 'Editar evento' : 'Nuevo evento' ?></h1>
    <p class="mb-0"><?= e(Vista::nombreCompletoGrupo($grupo->nombre)) ?></p>
</div>

<section class="tarjeta-blanca-borde p-4">
    <form data-accion="evento.guardar">
        <?= Csrf::campo() ?>
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id" value="<?= (int) $evento->id ?>">
        <?php else: ?>
            <input type="hidden" name="id_grupo" value="<?= (int) $grupo->id ?>">
        <?php endif; ?>
        <input type="hidden" name="volver" value="<?= e('views/pages/eventos.php?id_grupo=' . $grupo->id) ?>">

        <h2 class="h5 texto-violeta fw-bold mb-3">Datos del evento</h2>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control" id="titulo" name="titulo" maxlength="200" required value="<?= e($evento->titulo ?? '') ?>">
            </div>
            <div class="col-12">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?= e($evento->descripcion ?? '') ?></textarea>
            </div>
            <div class="col-md-4">
                <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required value="<?= e($evento->fechaInicio ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="fecha_fin" class="form-label">Fecha de fin <span class="text-muted">(opcional)</span></label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= e($evento->fechaFin ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="prioridad" class="form-label">Prioridad</label>
                <select class="form-select" id="prioridad" name="prioridad">
                    <?php foreach (Prioridad::cases() as $p): ?>
                        <option value="<?= e($p->value) ?>" <?= ($evento?->prioridad ?? Prioridad::Media) === $p ? 'selected' : '' ?>><?= e($p->etiqueta()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="es_publico" name="es_publico" value="1" <?= ($evento?->esPublico ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="es_publico">Público <span class="text-muted">(visible para Supervisores)</span></label>
                </div>
            </div>
        </div>

        <h2 class="h5 texto-violeta fw-bold mb-3">Alcance</h2>
        <div class="mb-3">
            <?php if ($opciones->puedeTodoElGrupo): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="alcance" id="alcanceGrupo" value="grupo" <?= $alcanceActual === 'grupo' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="alcanceGrupo">Todo el grupo</label>
                </div>
            <?php endif; ?>
            <?php if ($opciones->secciones !== []): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="alcance" id="alcanceSecciones" value="secciones" <?= $alcanceActual === 'secciones' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="alcanceSecciones">Algunas secciones</label>
                </div>
                <div class="ms-4 mb-2" id="listaSecciones">
                    <?php foreach ($opciones->secciones as $s): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="secciones[]" value="<?= (int) $s->id ?>" id="sec-<?= (int) $s->id ?>"
                                   <?= in_array((int) $s->id, $idSecciones, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sec-<?= (int) $s->id ?>"><?= e($s->nombre) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="boton-primario px-4 py-2 fw-bold">
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i><?= $esEdicion ? 'Guardar cambios' : 'Crear evento' ?>
            </button>
            <a class="btn btn-outline-secondary" href="<?= e(url('views/pages/eventos.php?id_grupo=' . $grupo->id)) ?>">Cancelar</a>
        </div>
    </form>
</section>

<script>
// Habilitar/deshabilitar las casillas de secciones según el alcance.
(function () {
    var radios = document.querySelectorAll('input[name="alcance"]');
    var lista = document.getElementById('listaSecciones');
    if (!radios.length || !lista) return;
    function actualizar() {
        var esSecciones = document.querySelector('input[name="alcance"]:checked');
        lista.style.opacity = esSecciones && esSecciones.value === 'secciones' ? '1' : '0.5';
        lista.querySelectorAll('input').forEach(function (cb) {
            cb.disabled = !(esSecciones && esSecciones.value === 'secciones');
        });
    }
    radios.forEach(function (r) { r.addEventListener('change', actualizar); });
    actualizar();
})();
</script>
<?php require __DIR__ . '/../includes/pie.php'; ?>
