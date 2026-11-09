<?php
// views/pages/secciones.php
//
// Vista general de secciones: todas las secciones a las que el usuario tiene
// acceso, agrupadas por grupo, con las tarjetas de rama.

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\SeccionController;
use Educarama\Helper\Vista;

$datos = Pagina::cargar(fn () => (new SeccionController())->paginaSecciones());
$grupos = $datos['grupos'];

$tituloPagina = 'Secciones';
require __DIR__ . '/../includes/cabecera.php';
?>
<section class="barra-superior-inst mb-4">
    <h1 class="fw-bold mb-1">Secciones</h1>
    <p class="mb-0">Todas las secciones a las que tenés acceso.</p>
</section>

<?php if ($grupos === []): ?>
    <section class="tarjeta-blanca-borde p-4">
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No tenés acceso a ninguna sección.</p>
    </section>
<?php else: ?>
    <?php foreach ($grupos as $grupoPantalla): ?>
        <?php $idGrupo = (int) $grupoPantalla->grupo->id; ?>
        <section class="mb-4" aria-labelledby="grupo-sec-<?= $idGrupo ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 texto-violeta fw-bold mb-0" id="grupo-sec-<?= $idGrupo ?>">
                    <?= e(Vista::nombreCompletoGrupo($grupoPantalla->grupo->nombre)) ?>
                    <?php if ($grupoPantalla->nivel !== null): ?>
                        <span class="small text-muted fw-normal ms-1">· <?= e($grupoPantalla->nivel->etiqueta()) ?></span>
                    <?php endif; ?>
                </h2>
                <a href="<?= e(url('views/pages/grupo.php?id=' . $idGrupo)) ?>" class="btn btn-outline-secondary btn-sm">
                    Ver grupo <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="row g-3">
                <?php foreach ($grupoPantalla->secciones as $seccion): ?>
                    <?php
                    $contenido = '<div class="rama-icono"><i class="bi ' . e(Vista::iconoRama($seccion->rama->id)) . '" aria-hidden="true"></i></div>'
                        . '<div><h3>' . e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) . '</h3>'
                        . '<p>' . e($seccion->rama->titulo) . ($seccion->nivel !== null ? ' · ' . e($seccion->nivel->etiqueta()) : '') . '</p></div>';
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <?php if ($seccion->nivel !== null): ?>
                            <a class="rama-card text-decoration-none <?= e(Vista::claseRama($seccion->rama->id)) ?>"
                               href="<?= e(url('views/pages/perfil_seccion.php?id=' . $seccion->seccion->id)) ?>">
                                <?= $contenido ?>
                            </a>
                        <?php else: ?>
                            <div class="rama-card <?= e(Vista::claseRama($seccion->rama->id)) ?> opacity-75">
                                <?= $contenido ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/pie.php'; ?>
