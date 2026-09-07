<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tituloPagina = "Gestionar avisos";

require_once __DIR__ . '/../../src/Controladores/AvisoControlador.php';


try {
    $controlador->procesarAcciones();

    $avisoEditar = $controlador->obtenerAvisoEditar();
    $avisos = $controlador->listarAvisos();
    $filtro = $controlador->obtenerFiltroActual();

} catch (Exception $e) {
    $error = $e->getMessage();

    $avisoEditar = null;
    $avisos = [];
    $filtro = $_GET['filtro'] ?? 'todos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Educarama - Avisos</title>

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
            <h1 class="fw-bold mb-1">Avisos
            </h1>
            <p class="mb-0">Administrá los avisos que se muestran en la pantalla de inicio.</p>
        </div>


        <div class="tarjeta-blanca-borde p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h4 texto-violeta fw-bold mb-0">Avisos cargados</h2>

                <div class="btn-group">
                    <a href="/Educarama/views/pages/gestionar_avisos.php?filtro=todos" class="btn btn-outline-primary <?= $filtro === 'todos' ? 'active' : '' ?>">Todos</a>
                    <a href="/Educarama/views/pages/gestionar_avisos.php?filtro=activos" class="btn btn-outline-primary <?= $filtro === 'activos' ? 'active' : '' ?>">Activos</a>
                    <a href="/Educarama/views/pages/gestionar_avisos.php?filtro=inactivos" class="btn btn-outline-primary <?= $filtro === 'inactivos' ? 'active' : '' ?>">Inactivos</a>
                </div>
            </div>

            <?php if (!empty($avisos)): ?>
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
                            <?php foreach ($avisos as $aviso): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($aviso['titulo']) ?></strong><br>
                                        <small><?= htmlspecialchars($aviso['descripcion']) ?></small>
                                    </td>
                                    <td><?= ucfirst(htmlspecialchars($aviso['prioridad'])) ?></td>
                                    <td><?= htmlspecialchars($aviso['fecha_inicio']) ?></td>
                                    <td><?= $aviso['fecha_fin'] ? htmlspecialchars($aviso['fecha_fin']) : 'Sin fin' ?></td>
                                    <td>
                                        <?php if ($aviso['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="/Educarama/views/pages/gestionar_avisos.php?editar=<?= $aviso['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php if ($aviso['activo']): ?>
                                            <a
                                                href="#"
                                                class="btn btn-sm btn-danger btn-desactivar"
                                                data-id="<?= $aviso['id'] ?>"
                                                data-titulo="<?= htmlspecialchars($aviso['titulo']) ?>"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a 
                                                href="/Educarama/views/pages/gestionar_avisos.php?accion=reactivar&id=<?= $aviso['id'] ?>" 
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
                <p>No hay avisos cargados.</p>
            <?php endif; ?>
            
            <!-- BOTONES DE ACCIÓN -->
            <div class="row g-3">

          
                <div class="col-12 col-md-4">
                    <a 
                        href="gestionar_avisos.php" 
                        class="boton-primario w-100 py-3 fw-bold text-decoration-none text-center d-block">
                        <i class="bi bi-person-gear me-2"></i>Crear nuevo aviso
                        </a>
                </div>
            </div>
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

            title: '¿Desactivar aviso?',
            html: `
                <b>${titulo}</b><br><br>
                El aviso dejará de mostrarse en la pantalla de inicio.
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
                    'gestionar_avisos.php?accion=desactivar&id=' + id;

            }

        });

    });

});

</script>
</body>
</html>