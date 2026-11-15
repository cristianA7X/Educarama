<?php
// views/pages/accesos.php
declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\AccesoController;
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

require_once __DIR__ . '/../includes/cabecera.php';
?>
<!-- Resto de tu HTML se mantiene igual -->
<section class="barra-superior-inst mb-4">
    <h1 class="fw-bold mb-1">Roles</h1>
    <p class="mb-0"><a class="text-reset" href="<?= e(url($linkVolver)) ?>"><?= e($tituloCtx) ?></a></p>
</section>
<!-- ... -->