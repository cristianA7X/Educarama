
<?php
require_once __DIR__ . '/../../src/Controladores/AuthMiddleware.php';
require_once __DIR__ . '/../../src/Controladores/ProgresionController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tituloPagina = "Gestionar competencias";
?>

<select class="form-select select-accion" data-id-accion="1" data-id-competencia="5" data-id-prota="10">
    <option value="Pendiente">Pendiente</option>
    <option value="En curso">En curso</option>
    <option value="Realizada">Realizada</option>
</select>

<script>
document.querySelectorAll('.select-accion').forEach(select => {
    select.addEventListener('change', async function() {
        const payload = {
            tipo_actualizacion: 'accion',
            id_protagonista: this.dataset.idProta,
            id_accion: this.dataset.idAccion,
            id_competencia: this.dataset.idCompetencia,
            estado: this.value
        };

        const response = await fetch('/Educarama/src/Controladores/ProgresionController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        // Si el servidor detecta que se completó todo, lanza la alerta sugerida
        if (data.sugerencia && data.sugerencia.tipo === 'COMPETENCIA_LISTA') {
            Swal.fire({
                title: '¡Acciones completas!',
                text: data.sugerencia.mensaje,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Sí, adquirir competencia'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí harías otro fetch para cambiar el estado de la competencia
                    actualizarCompetencia(payload.id_competencia, 'Adquirida');
                }
            });
        }
    });
});
</script>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Educarama - <?= htmlspecialchars($tituloPagina) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Educarama/public/css/styles.css?v=1.5">
</head>

<body>
<?php
include __DIR__ . '/includes/barra_superior.php';
?>

    <div class="main-content-wrapper without-sidebar">
    <div class="container contenedor-principal  p-4">
        <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
        <?php endif; ?>


        <div class="barra-superior-inst mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            
            <div>
            <h1 class="fw-bold mb-1">Protagonistas</h1>
            <p class="mb-0">Seleccioná un protagonista para ver su perfil completo.</p>
            </div>
            <a href="/Educarama/views/pages/protagonistas.php" class="boton-primario px-4 py-2 fw-bold text-decoration-none">
                <i class="me-2"></i>Ver cards de protagonistas   
            </a>
        </div>


        <div class="tarjeta-blanca-borde p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h4 texto-violeta fw-bold mb-0">Protagonistas cargados</h2>

            </div>

            <?php if (!empty($protagonistas)): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nombre y Apellido</th>
                                <th>Edad</th>
                                <th>Patrulla</th>
                                <th>Rama</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($protagonistas as $protagonista): ?>
                                <tr>
                                    <td>
                                        <a href="/Educarama/views/pages/perfil_protagonista.php?id=<?= $protagonista['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($protagonista['nombre']) ?> <?= htmlspecialchars($protagonista['apellido']) ?>
                                        </a>
                                    
                                    <td>
                                    <?php
                                    if (!empty($protagonista['fecha_nacimiento'])) {
                                        $fechaNacimiento = new DateTime($protagonista['fecha_nacimiento']);
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fechaNacimiento)->y;
                                        echo $edad . " años";
                                    } else {
                                        echo "<span class='text-muted'>Sin datos</span>";
                                    }
                                    ?>
                                    </td>
                                    <td><?= htmlspecialchars($protagonista['patrulla'] ?? 'Sin patrulla') ?></td>
                                    <td><?= htmlspecialchars($protagonista['rama'] ?? 'Sin rama') ?></td>
                                    

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay protagonistas cargados.</p>
            <?php endif; ?>
            
 
        </div>
            <!-- BOTONES DE ACCIÓN -->
            <div class="row g-3 mt-3">
          
                <div class="col-12 col-md-4">
                    <a 
                        href="alta_protagonista.php" 
                        class="boton-primario w-100 py-3 fw-bold text-decoration-none text-center d-block">
                        <i class="bi bi-person-gear me-2"></i>Crear nuevo protagonista
                        </a>
                </div>
            </div>

    </div>
</div>

<?php include __DIR__ . '/includes/barra_inferior.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>