<?php
// views/pages/registro.php
//
// Crear una cuenta. Queda sin accesos: después se pide el alta del grupo
// (solicitud_alta.php) o alguien con rol de Jefe la suma a uno existente.

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\UsuarioController;
use Educarama\Helper\Csrf;

Pagina::cargar(fn () => $app->obtener(UsuarioController::class)->paginaRegistro());

$tituloPagina = 'Crear una cuenta';
$sinMenu = true;
$contenedorAngosto = true;
require __DIR__ . '/../includes/cabecera.php';
?>
<div class="barra-superior-inst mb-4">
    <h1 class="h3 fw-bold mb-0">Crear una cuenta</h1>
</div>

<div class="tarjeta-blanca-borde p-4">
    <form data-accion="usuario.registrar">
        <?= Csrf::campo() ?>

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre y apellido</label>
            <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100"
                   autocomplete="name" required autofocus>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com"
                   maxlength="150" autocomplete="username" required>
            <div class="form-text">Con este correo te van a poder sumar a un grupo.</div>
        </div>

        <div class="mb-3">
            <label for="contrasenia" class="form-label">Contraseña</label>
            <div class="input-group">
                <input type="password" class="form-control" id="contrasenia" name="contrasenia"
                       autocomplete="new-password" required>
                <button class="btn btn-outline-secondary" type="button" data-ver-contrasenia="#contrasenia"
                        aria-label="Mostrar u ocultar la contraseña" aria-pressed="false">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label for="contrasenia_repetida" class="form-label">Repetí la contraseña</label>
            <input type="password" class="form-control" id="contrasenia_repetida" name="contrasenia_repetida"
                   autocomplete="new-password" required>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="boton-primario px-4 py-2 fw-bold">Crear la cuenta</button>
            <a href="<?= e(url('views/pages/login.php')) ?>" class="btn btn-outline-secondary px-4 py-2">Ya tengo una cuenta</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/pie.php'; ?>
