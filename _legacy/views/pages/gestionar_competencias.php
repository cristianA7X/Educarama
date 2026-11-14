<?php
// views/pages/gestionar_competencias.php

require_once __DIR__ . '/../../src/Controladores/AuthMiddleware.php';
require_once __DIR__ . '/../../src/Controladores/ProtagonistaController.php';
require_once __DIR__ . '/../../src/Datos/CompetenciaRepository.php';
require_once __DIR__ . '/../../src/Helpers/RamaHelper.php';

$tituloPagina = "Gestionar competencias";

// --- Protagonista   ---
$controlador  = new ProtagonistaController();
$protagonista = $controlador->obtenerPerfilDesdeUrl();

if (!$protagonista) {
    header("Location: protagonistas.php");
    exit;
}

// --- Datos de competencias ---
$error = null;
$senderos = [];
$competenciasPorSendero = [];

try {
    $repo     = new CompetenciaRepository();
    $senderos = $repo->obtenerSenderos();

    foreach ($repo->obtenerCompetenciasPorProtagonista((int) $protagonista['id']) as $c) {
        $competenciasPorSendero[(int) $c['id_sendero']][] = $c;
    }
} catch (Throwable $e) {
    $error = "No se pudieron cargar las competencias: " . $e->getMessage();
}

// --- Totales ---
$totalGeneral = 0;
$logradasGeneral = 0;
foreach ($competenciasPorSendero as $lista) {
    foreach ($lista as $c) {
        $totalGeneral++;
        if ((int) $c['desarrollada'] === 1) {
            $logradasGeneral++;
        }
    }
}
$porcentaje = $totalGeneral > 0 ? (int) round($logradasGeneral * 100 / $totalGeneral) : 0;

// Íconos por sendero (por id; fallback genérico)
$iconosSendero = [
    1 => 'bi-heart-pulse',
    2 => 'bi-people',
    3 => 'bi-tree',
    4 => 'bi-lightbulb',
];

$claseRama = claseRama($protagonista['id_categoria_rama'] ?? null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educarama - <?= htmlspecialchars($tituloPagina) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Educarama/public/css/styles.css?v=1.5">

    <style>
        .tabs-competencias .nav-link { color: inherit; font-weight: 700; white-space: nowrap; }
        .tabs-competencias .nav-link.active { border-bottom-width: 3px; }
        .competencia-item { cursor: pointer; }
        .competencia-item.lograda { background-color: rgba(25, 135, 84, .08); }
        .competencia-item .codigo { min-width: 2.25rem; }
        .competencia-item.guardando { opacity: .6; pointer-events: none; }
        .scroll-tabs { overflow-x: auto; flex-wrap: nowrap; }
    </style>
</head>

<body class="<?= $claseRama ?>">
<?php include __DIR__ . '/includes/barra_superior.php'; ?>

<div class="main-content-wrapper without-sidebar">
    <div class="container contenedor-principal p-4">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ENCABEZADO -->
        <div class="barra-superior-inst mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="fw-bold mb-1">
                    <?= htmlspecialchars($protagonista['nombre']) ?> <?= htmlspecialchars($protagonista['apellido']) ?>
                </h1>
                <p class="mb-0">
                    Marcá las competencias que ya desarrolló
                    <?php if (!empty($protagonista['rama'])): ?>
                        · Rama: <strong><?= htmlspecialchars($protagonista['rama']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <a href="/Educarama/views/pages/perfil_protagonista.php?id=<?= (int) $protagonista['id'] ?>"
               class="boton-primario px-4 py-2 fw-bold text-decoration-none">
                <i class="bi bi-arrow-left me-2"></i>Volver al perfil
            </a>
        </div>

        <div class="tarjeta-blanca-borde p-4">

            <!-- PROGRESO GENERAL -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold texto-violeta">Progreso general</span>
                    <span class="fw-bold">
                        <span id="logradasGeneral"><?= $logradasGeneral ?></span> /
                        <span id="totalGeneral"><?= $totalGeneral ?></span>
                        (<span id="porcentajeGeneral"><?= $porcentaje ?></span>%)
                    </span>
                </div>
                <div class="progress" role="progressbar" aria-label="Progreso general de competencias" style="height: 12px;">
                    <div id="barraGeneral" class="progress-bar bg-success" style="width: <?= $porcentaje ?>%"></div>
                </div>
            </div>

            <?php if (empty($senderos)): ?>
                <p class="text-muted mb-0">No hay senderos cargados.</p>
            <?php else: ?>

            <!-- PESTAÑAS -->
            <ul class="nav nav-tabs tabs-competencias scroll-tabs mb-3" id="tabsSenderos" role="tablist">
                <?php foreach ($senderos as $i => $s):
                    $idS    = (int) $s['id'];
                    $lista  = $competenciasPorSendero[$idS] ?? [];
                    $logr   = count(array_filter($lista, fn($c) => (int) $c['desarrollada'] === 1));
                    $icono  = $iconosSendero[$idS] ?? 'bi-award';
                ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>"
                                id="tab-<?= $idS ?>"
                                data-bs-toggle="tab"
                                data-bs-target="#panel-<?= $idS ?>"
                                type="button" role="tab"
                                aria-controls="panel-<?= $idS ?>"
                                aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                            <i class="bi <?= $icono ?> me-1"></i>
                            <?= htmlspecialchars(preg_replace('/^Sendero (de la |del |de las |de los )?/i', '', $s['tipo'])) ?>
                            <span class="badge bg-secondary ms-1" data-badge-sendero="<?= $idS ?>">
                                <span data-logradas-sendero="<?= $idS ?>"><?= $logr ?></span>/<?= count($lista) ?>
                            </span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- CONTENIDO DE LAS PESTAÑAS -->
            <div class="tab-content" id="contenidoSenderos">
                <?php foreach ($senderos as $i => $s):
                    $idS   = (int) $s['id'];
                    $lista = $competenciasPorSendero[$idS] ?? [];
                ?>
                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                         id="panel-<?= $idS ?>" role="tabpanel"
                         aria-labelledby="tab-<?= $idS ?>" tabindex="0">

                        <h2 class="h5 texto-violeta fw-bold mb-3"><?= htmlspecialchars($s['tipo']) ?></h2>

                        <?php if (empty($lista)): ?>
                            <p class="text-muted">
                                <i class="bi bi-info-circle me-2"></i>
                                No hay competencias cargadas en este sendero para la rama del protagonista.
                            </p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($lista as $c):
                                    $cid     = (int) $c['id'];
                                    $inputId = 'comp-' . $cid;
                                    $lograda = (int) $c['desarrollada'] === 1;
                                ?>
                                    <label class="list-group-item competencia-item d-flex align-items-start gap-3 <?= $lograda ? 'lograda' : '' ?>"
                                           for="<?= $inputId ?>">
                                        <input class="form-check-input mt-1 flex-shrink-0 check-competencia"
                                               type="checkbox"
                                               id="<?= $inputId ?>"
                                               style="width: 1.25rem; height: 1.25rem;"
                                               data-id-competencia="<?= $cid ?>"
                                               data-id-sendero="<?= $idS ?>"
                                               <?= $lograda ? 'checked' : '' ?>>
                                        <span class="badge bg-light text-dark border codigo"><?= (int) $c['codigo'] ?></span>
                                        <span class="flex-grow-1"><?= htmlspecialchars($c['descripcion'] ?? '') ?></span>
                                        <i class="bi bi-check-circle-fill text-success icono-ok <?= $lograda ? '' : 'd-none' ?>"></i>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/barra_inferior.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const ID_PROTAGONISTA = <?= (int) $protagonista['id'] ?>;
const ENDPOINT = '/Educarama/src/Controladores/ProgresionController.php';

// --- Recalcula contadores (pestañas + progreso general) a partir del DOM ---
function actualizarContadores() {
    const todos = document.querySelectorAll('.check-competencia');
    const logradas = document.querySelectorAll('.check-competencia:checked').length;
    const total = todos.length;
    const pct = total > 0 ? Math.round(logradas * 100 / total) : 0;

    document.getElementById('logradasGeneral').textContent = logradas;
    document.getElementById('totalGeneral').textContent = total;
    document.getElementById('porcentajeGeneral').textContent = pct;
    document.getElementById('barraGeneral').style.width = pct + '%';

    document.querySelectorAll('[data-logradas-sendero]').forEach(el => {
        const id = el.dataset.logradasSendero;
        el.textContent = document.querySelectorAll(
            '.check-competencia[data-id-sendero="' + id + '"]:checked'
        ).length;
    });
}

function marcarVisual(checkbox) {
    const item = checkbox.closest('.competencia-item');
    item.classList.toggle('lograda', checkbox.checked);
    item.querySelector('.icono-ok').classList.toggle('d-none', !checkbox.checked);
}

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
});

document.querySelectorAll('.check-competencia').forEach(checkbox => {
    checkbox.addEventListener('change', async function () {
        const item = this.closest('.competencia-item');
        const nuevoValor = this.checked;

        // Optimista: reflejamos el cambio y bloqueamos mientras guarda
        marcarVisual(this);
        actualizarContadores();
        item.classList.add('guardando');

        try {
            const response = await fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tipo_actualizacion: 'competencia',
                    id_protagonista: ID_PROTAGONISTA,
                    id_competencia: parseInt(this.dataset.idCompetencia, 10),
                    desarrollada: nuevoValor
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'No se pudo guardar el cambio.');
            }

            Toast.fire({ icon: 'success', title: nuevoValor ? 'Competencia marcada' : 'Competencia desmarcada' });
        } catch (err) {
            // Revertimos si falló
            this.checked = !nuevoValor;
            marcarVisual(this);
            actualizarContadores();
            Toast.fire({ icon: 'error', title: err.message || 'Error de conexión' });
        } finally {
            item.classList.remove('guardando');
        }
    });
});
</script>
</body>
</html>
