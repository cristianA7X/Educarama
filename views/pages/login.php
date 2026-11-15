<?php
// views/pages/login.php
//
// Iniciar sesión. Con sesión abierta, el Controller redirige al destino.
// Recibe ?volver= (una página del proyecto) para volver adonde se quería ir.

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\UsuarioController;
use Educarama\Helper\Csrf;

$datos = Pagina::cargar(fn () => $app->obtener(UsuarioController::class)->paginaLogin($_GET));

$tituloPagina = 'Iniciar sesión';
$sinMenu = true;
$contenedorAngosto = true;
require __DIR__ . '/../includes/cabecera.php';
?>
<div class="barra-superior-inst mb-4">
    <h1 class="h3 fw-bold mb-0">Iniciar sesión</h1>
</div>

<div class="tarjeta-blanca-borde p-4">
    <form data-accion="usuario.iniciarSesion">
        <?= Csrf::campo() ?>
        <input type="hidden" name="volver" value="<?= e($datos['volver']) ?>">

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com"
                   autocomplete="username" required autofocus>
        </div>

        <div class="mb-4">
            <label for="contrasenia" class="form-label">Contraseña</label>
            <div class="input-group">
                <input type="password" class="form-control" id="contrasenia" name="contrasenia"
                       autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" data-ver-contrasenia="#contrasenia"
                        aria-label="Mostrar u ocultar la contraseña" aria-pressed="false">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="boton-primario px-4 py-2 fw-bold">Iniciar sesión</button>
            <a href="<?= e(url('views/pages/registro.php')) ?>" class="btn btn-outline-secondary px-4 py-2">Crear una cuenta</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/pie.php'; ?>
