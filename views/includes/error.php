<?php
// views/includes/error.php
//
// Página de error que muestra Educarama\Controller\Pagina (403, 404, 500...).
// Recibe $codigo (int) y $mensaje (string, ya apto para el usuario).
//
// Va sin menú a propósito: si el error fuera justamente de cargar el menú,
// pedirlo de nuevo volvería a fallar y a mostrar esta misma página.

declare(strict_types=1);

$titulos = [
    403 => ['Sin permiso', 'bi-shield-lock'],
    404 => ['No encontrado', 'bi-search'],
    419 => ['La sesión venció', 'bi-clock-history'],
];
[$tituloError, $iconoError] = $titulos[$codigo] ?? ['No se pudo completar', 'bi-exclamation-triangle'];

$tituloPagina = $tituloError;
$sinMenu = true;
$contenedorAngosto = true;
require __DIR__ . '/cabecera.php';
?>
<div class="text-center py-4">
    <p class="display-3 texto-violeta mb-2"><i class="bi <?= e($iconoError) ?>" aria-hidden="true"></i></p>
    <h1 class="h3 fw-bold texto-violeta"><?= e($tituloError) ?></h1>
    <p class="mb-4"><?= e($mensaje) ?></p>
    <a class="boton-primario px-4 py-2 fw-bold text-decoration-none d-inline-block" href="<?= e(url('views/pages/inicio.php')) ?>">
        <i class="bi bi-house-door me-2" aria-hidden="true"></i>Volver al inicio
    </a>
</div>
<?php require __DIR__ . '/pie.php'; ?>
