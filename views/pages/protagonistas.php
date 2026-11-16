<?php
// views/pages/protagonistas.php
//
// Dos modos:
//   ?id_seccion=  → listado de cards de una sección (el original)
//   (sin parámetro) → nómina/tabla general con todos los protagonistas de las
//                     secciones accesibles, con datos de progresión y filtros

declare(strict_types=1);

$app = require_once __DIR__ . '/../../src/main.php';

use Educarama\Controller\Pagina;
use Educarama\Controller\ProtagonistaController;
use Educarama\Helper\Vista;

$controller = $app->obtener(ProtagonistaController::class);
$esNomina = !isset($_GET['id_seccion']);

if ($esNomina) {
    // ── Modo nómina general ──
    $datos = Pagina::cargar(fn () => $controller->paginaNomina());
    $nomina = $datos['nomina'];

    // Valores únicos para los filtros.
    $grupos = [];
    $secciones = [];
    $ramas = [];
    foreach ($nomina as $fila) {
        $grupos[$fila->idGrupo] = $fila->grupoNombre;
        $secciones[$fila->idSeccion] = $fila->seccionNombre;
        $ramas[$fila->idRama] = $fila->ramaTitulo;
    }
    asort($grupos);
    asort($secciones);
    asort($ramas);

    $tituloPagina = 'Protagonistas';
    require __DIR__ . '/../includes/cabecera.php';
    ?>
    <section class="barra-superior-inst mb-3">
        <h1 class="fw-bold mb-1">Protagonistas</h1>
        <p class="mb-0">Nómina general de todas las secciones a las que tenés acceso
            <span class="badge text-bg-secondary ms-1"><?= count($nomina) ?></span>
        </p>
    </section>

    <?php if ($nomina === []): ?>
        <section class="tarjeta-blanca-borde p-4">
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No hay protagonistas en las secciones a las que tenés acceso.</p>
        </section>
    <?php else: ?>
        <!-- Filtros -->
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-sm-6 col-md-3">
                <label for="buscarNomina" class="form-label visually-hidden">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" id="buscarNomina" placeholder="Buscar por nombre…" data-filtro="buscar">
                </div>
            </div>
            <?php if (count($grupos) > 1): ?>
                <div class="col-sm-4 col-md-2">
                    <select class="form-select" data-filtro="grupo">
                        <option value="">Todos los grupos</option>
                        <?php foreach ($grupos as $id => $nombre): ?>
                            <option value="<?= $id ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (count($secciones) > 1): ?>
                <div class="col-sm-4 col-md-2">
                    <select class="form-select" data-filtro="seccion">
                        <option value="">Todas las secciones</option>
                        <?php foreach ($secciones as $id => $nombre): ?>
                            <option value="<?= $id ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (count($ramas) > 1): ?>
                <div class="col-sm-4 col-md-2">
                    <select class="form-select" data-filtro="rama">
                        <option value="">Todas las ramas</option>
                        <?php foreach ($ramas as $id => $nombre): ?>
                            <option value="<?= $id ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabla -->
        <div class="tarjeta-blanca-borde p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaNomina">
                    <thead class="text-center">
                        <tr>
                            <th class="ps-3 text-start" data-orden="nombre" role="button">Nombre <i class="bi bi-arrow-down-up text-muted small" aria-hidden="true"></i></th>
                            <th>DNI</th>
                            <th data-orden="edad" role="button">Edad <i class="bi bi-arrow-down-up text-muted small" aria-hidden="true"></i></th>
                            <th>Sección</th>
                            <th>Grupo</th>
                            <th>Patrulla</th>
                            <th>Etapa actual</th>
                            <th data-orden="competencias" role="button">Competencias <i class="bi bi-arrow-down-up text-muted small" aria-hidden="true"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nomina as $fila): ?>
                            <?php $edad = Vista::edad($fila->fechaNacimiento); ?>
                            <tr class="fila-nomina" data-buscar="<?= e(mb_strtolower($fila->apellido . ' ' . $fila->nombre)) ?>"
                                data-grupo="<?= $fila->idGrupo ?>" data-seccion="<?= $fila->idSeccion ?>"
                                data-rama="<?= $fila->idRama ?>"
                                data-edad="<?= $edad ?? -1 ?>" data-competencias="<?= $fila->competenciasDesarrolladas ?>">
                                <td class="ps-3 fw-bold">
                                    <a class="text-decoration-none text-reset" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $fila->idProtagonista)) ?>">
                                        <?= e($fila->apellido) ?>, <?= e($fila->nombre) ?>
                                    </a>
                                </td>
                                <td class="text-center text-muted"><?= e(number_format($fila->dni, 0, '', '.')) ?></td>
                                <td class="text-center"><?= $edad !== null ? $edad : '<span class="text-muted">–</span>' ?></td>
                                <td>
                                    <?php $pictograma = Vista::pictogramaRama($fila->idRama); ?>
                                    <?php if ($pictograma !== null): ?>
                                        <img class="icono-oficial me-1" src="<?= e($pictograma) ?>" alt="">
                                    <?php else: ?>
                                        <i class="bi <?= e(Vista::iconoRama($fila->idRama)) ?> me-1" aria-hidden="true"></i>
                                    <?php endif; ?>
                                    <a class="text-decoration-none fw-semibold" style="color: <?= e(Vista::colorRama($fila->idRama)) ?>" href="<?= e(url('views/pages/protagonistas.php?id_seccion=' . $fila->idSeccion)) ?>">
                                        <?= e($fila->seccionNombre) ?>
                                    </a>
                                </td>
                                <td class="text-center"><?= e($fila->grupoNombre) ?></td>
                                <td class="text-center"><?= $fila->nombrePatrulla !== null ? e($fila->nombrePatrulla) : '<span class="text-muted">–</span>' ?></td>
                                <td class="text-center"><?= $fila->etapaActual !== null ? e($fila->etapaActual) : '<span class="text-muted">Sin etapa</span>' ?></td>
                                <td class="text-center text-muted"><?= $fila->competenciasDesarrolladas ?>/<?= $fila->competenciasTotal ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-muted small mt-3 d-none" id="sinResultadosNomina">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>No se encontraron protagonistas con ese filtro.
        </p>
    <?php endif; ?>

    <script>
    // Filtros y orden de la nómina, del lado del cliente.
    (function () {
        var buscar = document.querySelector('[data-filtro="buscar"]');
        var filtroGrupo = document.querySelector('[data-filtro="grupo"]');
        var filtroSeccion = document.querySelector('[data-filtro="seccion"]');
        var filtroRama = document.querySelector('[data-filtro="rama"]');
        var filas = document.querySelectorAll('.fila-nomina');
        var sinResultados = document.getElementById('sinResultadosNomina');
        if (!filas.length) return;

        // ── Filtrado ──
        function filtrar() {
            var texto = buscar ? buscar.value.toLowerCase().trim() : '';
            var grupo = filtroGrupo ? filtroGrupo.value : '';
            var seccion = filtroSeccion ? filtroSeccion.value : '';
            var rama = filtroRama ? filtroRama.value : '';
            var visibles = 0;
            filas.forEach(function (f) {
                var ok = (!texto || f.getAttribute('data-buscar').indexOf(texto) !== -1)
                      && (!grupo || f.getAttribute('data-grupo') === grupo)
                      && (!seccion || f.getAttribute('data-seccion') === seccion)
                      && (!rama || f.getAttribute('data-rama') === rama);
                f.style.display = ok ? '' : 'none';
                if (ok) visibles++;
            });
            if (sinResultados) sinResultados.classList.toggle('d-none', visibles > 0);
        }

        if (buscar) buscar.addEventListener('input', filtrar);
        if (filtroGrupo) filtroGrupo.addEventListener('change', filtrar);
        if (filtroSeccion) filtroSeccion.addEventListener('change', filtrar);
        if (filtroRama) filtroRama.addEventListener('change', filtrar);

        // ── Orden por columna ──
        var encabezados = document.querySelectorAll('[data-orden]');
        var tbody = document.querySelector('#tablaNomina tbody');
        var ordenActual = { campo: null, asc: true };

        encabezados.forEach(function (th) {
            th.style.cursor = 'pointer';
            th.addEventListener('click', function () {
                var campo = th.getAttribute('data-orden');
                if (ordenActual.campo === campo) {
                    ordenActual.asc = !ordenActual.asc;
                } else {
                    ordenActual.campo = campo;
                    ordenActual.asc = true;
                }

                var filasArr = Array.from(filas);
                filasArr.sort(function (a, b) {
                    var va, vb;
                    if (campo === 'edad' || campo === 'competencias') {
                        va = parseFloat(a.getAttribute('data-' + campo));
                        vb = parseFloat(b.getAttribute('data-' + campo));
                    } else {
                        // Nombre unificado: "Apellido, Nombre" en la primera columna.
                        va = a.querySelector('td:first-child').textContent.trim().toLowerCase();
                        vb = b.querySelector('td:first-child').textContent.trim().toLowerCase();
                    }
                    if (va < vb) return ordenActual.asc ? -1 : 1;
                    if (va > vb) return ordenActual.asc ? 1 : -1;
                    return 0;
                });
                filasArr.forEach(function (f) { tbody.appendChild(f); });

                // Indicador visual.
                encabezados.forEach(function (h) {
                    var icon = h.querySelector('i');
                    if (icon) icon.className = 'bi bi-arrow-down-up text-muted small';
                });
                var iconActivo = th.querySelector('i');
                if (iconActivo) {
                    iconActivo.className = 'bi ' + (ordenActual.asc ? 'bi-arrow-up' : 'bi-arrow-down') + ' small';
                }
            });
        });
    })();
    </script>

<?php
    require __DIR__ . '/../includes/pie.php';
    return;  // No seguir con el modo sección.
}

// ── Modo sección (cards + nómina) ──
$datos = Pagina::cargar(fn () => $controller->paginaListado($_GET));
$seccion = $datos['seccion'];
$protagonistas = $datos['protagonistas'];
$patrullas = $datos['patrullas'];
$nomina = $datos['nomina'];
$equipo = $seccion->pequenioEquipo();

$tituloPagina = 'Protagonistas · ' . Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id);
$claseRama = Vista::claseRama($seccion->rama->id);
require __DIR__ . '/../includes/cabecera.php';
?>
<section class="barra-superior-inst mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1 class="fw-bold mb-1">Protagonistas</h1>
        <p class="mb-0">
            <a class="text-reset" href="<?= e(url('views/pages/perfil_seccion.php?id=' . $seccion->seccion->id)) ?>"><?= e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) ?></a>
            · <?= e($seccion->rama->titulo) ?>
            <span class="badge text-bg-secondary ms-1"><?= count($protagonistas) ?></span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?php if ($protagonistas !== []): ?>
            <div class="btn-group btn-group-sm" role="group" aria-label="Vista">
                <button type="button" class="btn btn-light active" id="btnVistaCards" title="Tarjetas"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-light" id="btnVistaTabla" title="Tabla"><i class="bi bi-list-ul" aria-hidden="true"></i></button>
            </div>
        <?php endif; ?>
        <?php if ($seccion->puedeEditar()): ?>
            <a class="boton-primario px-3 py-2 fw-bold text-decoration-none" href="<?= e(url('views/pages/protagonista_form.php?id_seccion=' . $seccion->seccion->id)) ?>">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Nuevo protagonista
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if ($protagonistas !== []): ?>
    <!-- Filtros (compartidos por ambas vistas) -->
    <div class="row g-2 mb-3 align-items-end">
        <div class="col-sm-6 col-md-4">
            <label for="buscar" class="form-label visually-hidden">Buscar</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="text" class="form-control" id="buscar" placeholder="Buscar por nombre o apellido…">
            </div>
        </div>
        <?php if ($equipo !== null && $patrullas !== []): ?>
            <div class="col-sm-4 col-md-3">
                <label for="filtroPatrulla" class="form-label visually-hidden"><?= e($equipo) ?></label>
                <select class="form-select" id="filtroPatrulla">
                    <option value="">Todos</option>
                    <?php foreach ($patrullas as $p): ?>
                        <option value="<?= (int) $p->id ?>"><?= e($p->nombre) ?></option>
                    <?php endforeach; ?>
                    <option value="0">Sin <?= e(mb_strtolower($equipo)) ?></option>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vista cards -->
    <div id="vistaCards">
        <div class="row g-3" id="listaProtagonistas">
            <?php foreach ($protagonistas as $ficha): ?>
                <?php
                $persona = $ficha->persona;
                $prot = $ficha->protagonista;
                $edad = Vista::edad($persona->fechaNacimiento);
                $nombre = $persona->nombre . ' ' . $persona->apellido;
                ?>
                <div class="col-12 col-md-6 col-lg-4 item-protagonista"
                     data-nombre="<?= e(mb_strtolower($nombre)) ?>"
                     data-patrulla="<?= $prot->idPatrulla !== null ? (int) $prot->idPatrulla : '0' ?>">
                    <a class="protagonista-card text-decoration-none <?= e($claseRama) ?>"
                       href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $prot->id)) ?>">
                        <div class="protagonista-foto">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                        </div>
                        <div class="protagonista-info">
                            <h3><?= e($nombre) ?></h3>
                            <p>
                                <?php if ($edad !== null): ?>
                                    <?= $edad ?> años
                                <?php else: ?>
                                    <span class="text-muted">Sin fecha de nacimiento</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($equipo !== null): ?>
                                <p>
                                    <?php if ($ficha->nombrePatrulla !== null): ?>
                                        <?= e($ficha->nombrePatrulla) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin <?= e(mb_strtolower($equipo)) ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Vista tabla (nómina) -->
    <div id="vistaTabla" class="d-none">
        <div class="tarjeta-blanca-borde p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaNominaSeccion">
                    <thead class="text-center">
                        <tr>
                            <th class="ps-3 text-start" data-orden-sec="nombre" role="button">Nombre <i class="bi bi-arrow-down-up text-muted small" aria-hidden="true"></i></th>
                            <th>DNI</th>
                            <th data-orden-sec="edad" role="button">Edad <i class="bi bi-arrow-down-up text-muted small" aria-hidden="true"></i></th>
                            <th>Patrulla</th>
                            <th>Etapa actual</th>
                            <th data-orden-sec="competencias" role="button">Competencias <i class="bi bi-arrow-down-up text-muted small" aria-hidden="true"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nomina as $fila): ?>
                            <?php $edad = Vista::edad($fila->fechaNacimiento); ?>
                            <tr class="fila-nomina-sec"
                                data-buscar="<?= e(mb_strtolower($fila->apellido . ' ' . $fila->nombre)) ?>"
                                data-patrulla="<?= $fila->idPatrulla !== null ? $fila->idPatrulla : '0' ?>"
                                data-edad="<?= $edad ?? -1 ?>" data-competencias="<?= $fila->competenciasDesarrolladas ?>">
                                <td class="ps-3 fw-bold">
                                    <a class="text-decoration-none text-reset" href="<?= e(url('views/pages/perfil_protagonista.php?id=' . $fila->idProtagonista)) ?>">
                                        <?= e($fila->apellido) ?>, <?= e($fila->nombre) ?>
                                    </a>
                                </td>
                                <td class="text-center text-muted"><?= e(number_format($fila->dni, 0, '', '.')) ?></td>
                                <td class="text-center"><?= $edad !== null ? $edad : '<span class="text-muted">–</span>' ?></td>
                                <td class="text-center"><?= $fila->nombrePatrulla !== null ? e($fila->nombrePatrulla) : '<span class="text-muted">–</span>' ?></td>
                                <td class="text-center"><?= $fila->etapaActual !== null ? e($fila->etapaActual) : '<span class="text-muted">Sin etapa</span>' ?></td>
                                <td class="text-center text-muted"><?= $fila->competenciasDesarrolladas ?>/<?= $fila->competenciasTotal ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 d-none" id="sinResultados">
        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>No se encontraron protagonistas con ese filtro.
    </p>
<?php else: ?>
    <section class="tarjeta-blanca-borde p-4">
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>No hay protagonistas en esta sección.</p>
        <?php if ($seccion->puedeEditar()): ?>
            <a class="boton-primario px-3 py-2 fw-bold text-decoration-none d-inline-block mt-3" href="<?= e(url('views/pages/protagonista_form.php?id_seccion=' . $seccion->seccion->id)) ?>">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Dar de alta al primero
            </a>
        <?php endif; ?>
    </section>
<?php endif; ?>

<script>
(function () {
    // ── Toggle cards / tabla ──
    var btnCards = document.getElementById('btnVistaCards');
    var btnTabla = document.getElementById('btnVistaTabla');
    var vistaCards = document.getElementById('vistaCards');
    var vistaTabla = document.getElementById('vistaTabla');

    if (btnCards && btnTabla) {
        // Recordar preferencia.
        try { if (localStorage.getItem('protagonistas_vista') === 'tabla') btnTabla.click(); } catch(e) {}

        btnCards.addEventListener('click', function () {
            vistaCards.classList.remove('d-none');
            vistaTabla.classList.add('d-none');
            btnCards.classList.add('active');
            btnTabla.classList.remove('active');
            try { localStorage.setItem('protagonistas_vista', 'cards'); } catch(e) {}
        });
        btnTabla.addEventListener('click', function () {
            vistaCards.classList.add('d-none');
            vistaTabla.classList.remove('d-none');
            btnTabla.classList.add('active');
            btnCards.classList.remove('active');
            try { localStorage.setItem('protagonistas_vista', 'tabla'); } catch(e) {}
        });

        // Restaurar preferencia al cargar.
        try {
            if (localStorage.getItem('protagonistas_vista') === 'tabla') {
                vistaCards.classList.add('d-none');
                vistaTabla.classList.remove('d-none');
                btnTabla.classList.add('active');
                btnCards.classList.remove('active');
            }
        } catch(e) {}
    }

    // ── Filtrado (funciona en ambas vistas) ──
    var buscar = document.getElementById('buscar');
    var filtroPatrulla = document.getElementById('filtroPatrulla');
    var itemsCards = document.querySelectorAll('.item-protagonista');
    var filasTabla = document.querySelectorAll('.fila-nomina-sec');
    var sinResultados = document.getElementById('sinResultados');
    if (!buscar) return;

    function filtrar() {
        var texto = buscar.value.toLowerCase().trim();
        var patrulla = filtroPatrulla ? filtroPatrulla.value : '';
        var visibles = 0;

        // Cards.
        itemsCards.forEach(function (item) {
            var ok = (!texto || item.getAttribute('data-nombre').indexOf(texto) !== -1)
                  && (!patrulla || item.getAttribute('data-patrulla') === patrulla);
            item.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        // Tabla.
        var visiblesTabla = 0;
        filasTabla.forEach(function (f) {
            var ok = (!texto || f.getAttribute('data-buscar').indexOf(texto) !== -1)
                  && (!patrulla || f.getAttribute('data-patrulla') === patrulla);
            f.style.display = ok ? '' : 'none';
            if (ok) visiblesTabla++;
        });

        // Mostrar "sin resultados" si la vista activa no tiene nada.
        var enTabla = vistaTabla && !vistaTabla.classList.contains('d-none');
        var totalVisibles = enTabla ? visiblesTabla : visibles;
        if (sinResultados) sinResultados.classList.toggle('d-none', totalVisibles > 0);
    }

    buscar.addEventListener('input', filtrar);
    if (filtroPatrulla) filtroPatrulla.addEventListener('change', filtrar);

    // ── Orden por columna (tabla de sección) ──
    var encabezados = document.querySelectorAll('[data-orden-sec]');
    var tbody = document.querySelector('#tablaNominaSeccion tbody');
    var ordenActual = { campo: null, asc: true };

    encabezados.forEach(function (th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            var campo = th.getAttribute('data-orden-sec');
            if (ordenActual.campo === campo) {
                ordenActual.asc = !ordenActual.asc;
            } else {
                ordenActual.campo = campo;
                ordenActual.asc = true;
            }

            var filasArr = Array.from(filasTabla);
            filasArr.sort(function (a, b) {
                var va, vb;
                if (campo === 'edad' || campo === 'competencias') {
                    va = parseFloat(a.getAttribute('data-' + campo));
                    vb = parseFloat(b.getAttribute('data-' + campo));
                } else {
                    va = a.querySelector('td:first-child').textContent.trim().toLowerCase();
                    vb = b.querySelector('td:first-child').textContent.trim().toLowerCase();
                }
                if (va < vb) return ordenActual.asc ? -1 : 1;
                if (va > vb) return ordenActual.asc ? 1 : -1;
                return 0;
            });
            filasArr.forEach(function (f) { tbody.appendChild(f); });

            encabezados.forEach(function (h) {
                var icon = h.querySelector('i');
                if (icon) icon.className = 'bi bi-arrow-down-up text-muted small';
            });
            var iconActivo = th.querySelector('i');
            if (iconActivo) {
                iconActivo.className = 'bi ' + (ordenActual.asc ? 'bi-arrow-up' : 'bi-arrow-down') + ' small';
            }
        });
    });
})();
</script>
<?php require __DIR__ . '/../includes/pie.php'; ?>
