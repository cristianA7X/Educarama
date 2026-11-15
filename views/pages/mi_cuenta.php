<?php
// views/pages/mi_cuenta.php
//
// Los datos de la cuenta (solo lectura: el nombre y el correo no se editan
// desde la aplicación) y el cambio de contraseña.

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\UsuarioController;
use Educarama\Helper\Csrf;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => $app->obtener(UsuarioController::class)->paginaMiCuenta());
$usuario = $datos['usuario'];
$grupos = $datos['grupos'];

$tituloPagina = 'Mi cuenta';
$contenedorAngosto = true;
require __DIR__ . '/../includes/cabecera.php';
?>
<div class="barra-superior-inst mb-4">
    <h1 class="h3 fw-bold mb-0">Mi cuenta</h1>
</div>

<section class="tarjeta-blanca-borde p-4 mb-4">
    <h2 class="h5 texto-violeta fw-bold">Mis datos</h2>
    <dl class="mb-0">
        <dt>Nombre</dt>
        <dd><?= e($usuario->nombre) ?></dd>
        <dt>Correo electrónico</dt>
        <dd class="mb-0"><?= e($usuario->email) ?></dd>
    </dl>
</section>

<?php if ($grupos !== []): ?>
<section class="tarjeta-blanca-borde p-4 mb-4">
    <h2 class="h5 texto-violeta fw-bold">Mis roles</h2>
    <?php foreach ($grupos as $grupoPantalla): ?>
        <div class="mb-3">
            <p class="fw-bold mb-1">
                <i class="bi bi-house-door me-1" aria-hidden="true"></i><?= e(Vista::nombreCompletoGrupo($grupoPantalla->grupo->nombre)) ?>
                <?php if ($grupoPantalla->nivel !== null): ?>
                    <span class="badge text-bg-secondary ms-1"><?= e($grupoPantalla->nivel->etiqueta()) ?> del grupo</span>
                <?php endif; ?>
            </p>
            <?php
            $seccionesConRol = array_filter($grupoPantalla->secciones, fn ($s) => $s->nivel !== null);
            if ($seccionesConRol !== []):
            ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($seccionesConRol as $secPantalla): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                            <span>
                                <?php $pictograma = Vista::pictogramaRama($secPantalla->rama->id); ?>
                                <?php if ($pictograma !== null): ?>
                                    <img class="icono-oficial me-1" src="<?= e($pictograma) ?>" alt="">
                                <?php else: ?>
                                    <i class="bi <?= e(Vista::iconoRama($secPantalla->rama->id)) ?> me-1" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= e(Vista::nombreCompletoSeccion($secPantalla->seccion->nombre, $secPantalla->rama->id)) ?>
                            </span>
                            <span class="badge text-bg-secondary"><?= e($secPantalla->nivel->etiqueta()) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="tarjeta-blanca-borde p-4">
    <h2 class="h5 texto-violeta fw-bold">Cambiar la contraseña</h2>
    <form data-accion="usuario.cambiarContrasenia" data-reiniciar>
        <?= Csrf::campo() ?>

        <div class="mb-3">
            <label for="actual" class="form-label">Contraseña actual</label>
            <input type="password" class="form-control" id="actual" name="actual" autocomplete="current-password" required>
        </div>

        <div class="mb-3">
            <label for="nueva" class="form-label">Contraseña nueva</label>
            <div class="input-group">
                <input type="password" class="form-control" id="nueva" name="nueva" autocomplete="new-password" required>
                <button class="btn btn-outline-secondary" type="button" data-ver-contrasenia="#nueva"
                        aria-label="Mostrar u ocultar la contraseña" aria-pressed="false">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label for="nueva_repetida" class="form-label">Repetí la contraseña nueva</label>
            <input type="password" class="form-control" id="nueva_repetida" name="nueva_repetida" autocomplete="new-password" required>
        </div>

        <button type="submit" class="boton-primario px-4 py-2 fw-bold">Cambiar la contraseña</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/pie.php'; ?>
