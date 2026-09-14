<?php
// views/pages/gestionar_eventos.php

require_once __DIR__ . '/../../src/Controladores/AuthMiddleware.php';
require_once __DIR__ . '/../../src/Controladores/EventoControlador.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tituloPagina = "Gestionar eventos";


$controlador = new EventoControlador();

try {
    $controlador->procesarAcciones();

    $eventoEditar = $controlador->obtenerEventosParaEditar();
    $eventos = $controlador->listarEventos();
    $filtro = $controlador->obtenerFiltroActual();

} catch (Exception $e) {
    $error = $e->getMessage();

    $eventoEditar = null;
    $eventos = [];
    $filtro = $_GET['filtro'] ?? 'todos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Educarama - Eventos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Educarama/public/css/styles.css?v=1.5">
</head>

<body>
<?php
include __DIR__ . '/includes/barra_superior.php';
include __DIR__ . '/includes/menu_lateral.php';
?>

<div class="main-content-wrapper">
    <div class="container contenedor-principal my-4 p-4">
        <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
        <?php endif; ?>

        <div class="barra-superior-inst mb-4">
            <h1 class="fw-bold mb-1">
                <?= $eventoEditar ? 'Editar evento' : 'Crear evento' ?>
            </h1>
            <p class="mb-0">
                <?= $eventoEditar ? 'Modificá los datos del evento seleccionado.' : 'Completá los datos para crear un nuevo evento.' ?>
            </p>
        </div>

        <form method="POST" class="tarjeta-blanca-borde p-4 mb-4">
            <input type="hidden" name="id" value="<?= $eventoEditar ? htmlspecialchars($eventoEditar['id']) : '' ?>">

            <div class="mb-3">
                <label class="form-label fw-bold">Título</label>
                <input 
                    type="text" 
                    name="titulo" 
                    class="form-control" 
                    required
                    value="<?= $eventoEditar ? htmlspecialchars($eventoEditar['titulo']) : '' ?>"
                >
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3" required><?= $eventoEditar ? htmlspecialchars($eventoEditar['descripcion']) : '' ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Fecha inicio</label>
                    <input 
                        type="date" 
                        name="fecha_inicio" 
                        class="form-control" 
                        required
                        value="<?= $eventoEditar ? htmlspecialchars($eventoEditar['fecha_inicio']) : '' ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Fecha fin</label>
                    <input 
                        type="date" 
                        name="fecha_fin" 
                        class="form-control"
                        value="<?= $eventoEditar ? htmlspecialchars($eventoEditar['fecha_fin'] ?? '') : '' ?>"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Prioridad</label>
                    <select name="prioridad" class="form-select" required>
                        <option value="alta" <?= $eventoEditar && $eventoEditar['prioridad'] === 'alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="media" <?= !$eventoEditar || $eventoEditar['prioridad'] === 'media' ? 'selected' : '' ?>>Media</option>
                        <option value="baja" <?= $eventoEditar && $eventoEditar['prioridad'] === 'baja' ? 'selected' : '' ?>>Baja</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="boton-primario px-4 py-2 fw-bold">
                <i class="bi bi-save me-2"></i>
                <?= $eventoEditar ? 'Actualizar evento' : 'Guardar evento' ?>
            </button>

            <?php if ($eventoEditar): ?>
                <a href="/Educarama/views/pages/gestionar_eventos.php" class="btn btn-secondary ms-2">
                    Cancelar edición
                </a>
            <?php endif; ?>
        </form>

        <div class="tarjeta-blanca-borde p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h4 texto-violeta fw-bold mb-0">Eventos cargados</h2>

                <div class="btn-group">
                    <a href="/Educarama/views/pages/gestionar_eventos.php?filtro=todos" class="btn btn-outline-primary <?= $filtro === 'todos' ? 'active' : '' ?>">Todos</a>
                    <a href="/Educarama/views/pages/gestionar_eventos.php?filtro=activos" class="btn btn-outline-primary <?= $filtro === 'activos' ? 'active' : '' ?>">Activos</a>
                    <a href="/Educarama/views/pages/gestionar_eventos.php?filtro=inactivos" class="btn btn-outline-primary <?= $filtro === 'inactivos' ? 'active' : '' ?>">Inactivos</a>
                </div>
            </div>

            <?php if (!empty($eventos)): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Prioridad</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $evento): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($evento['titulo']) ?></strong><br>
                                        <small><?= htmlspecialchars($evento['descripcion']) ?></small>
                                    </td>
                                    <td><?= ucfirst(htmlspecialchars($evento['prioridad'])) ?></td>
                                    <td><?= htmlspecialchars($evento['fecha_inicio']) ?></td>
                                    <td><?= $evento['fecha_fin'] ? htmlspecialchars($evento['fecha_fin']) : 'Sin fin' ?></td>
                                    <td>
                                        <?php if ($evento['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="/Educarama/views/pages/gestionar_eventos.php?editar=<?= $evento['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php if ($evento['activo']): ?>
                                            <a
                                                href="#"
                                                class="btn btn-sm btn-danger btn-desactivar"
                                                data-id="<?= $evento['id'] ?>"
                                                data-titulo="<?= htmlspecialchars($evento['titulo']) ?>"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a 
                                                href="/Educarama/views/pages/gestionar_eventos.php?accion=reactivar&id=<?= $evento['id'] ?>" 
                                                class="btn btn-sm btn-success"
                                            >
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay eventos cargados.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/barra_inferior.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

document.querySelectorAll('.btn-desactivar').forEach(boton => {

    boton.addEventListener('click', function(e){

        e.preventDefault();

        let id = this.dataset.id;
        let titulo = this.dataset.titulo;

        Swal.fire({

            title: '¿Desactivar evento?',
            html: `
                <b>${titulo}</b><br><br>
                El evento dejará de mostrarse en la pantalla de inicio.
            `,
            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#45009e',
            cancelButtonColor: '#d33',

            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'

        }).then((result)=>{

            if(result.isConfirmed){

                window.location =
                    'gestionar_eventos.php?accion=desactivar&id=' + id;

            }

        });

    });

});

</script>
</body>
</html>