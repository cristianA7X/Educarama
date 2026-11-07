<?php
// views/pages/mi_cuenta.php
//
// Los datos de la cuenta (solo lectura: el nombre y el correo no se editan
// desde la aplicación) y el cambio de contraseña.

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\UsuarioController;
use Educarama\Helper\Csrf;

$datos = Pagina::cargar(fn () => (new UsuarioController())->paginaMiCuenta());
$usuario = $datos['usuario'];

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
