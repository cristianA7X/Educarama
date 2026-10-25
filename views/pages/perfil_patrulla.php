<?php
///views/pages/perfil_patrulla.php

require_once __DIR__ . '/../../src/Controladores/AuthMiddleware.php';
require_once __DIR__ . '/../../src/Controladores/PatrullaController.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tituloPagina = "Perfil de Patrulla";
$controlador = new PatrullaController();

$idPatrulla = $_GET['id'] ?? null;

try {
    if (!$idPatrulla) {
        throw new Exception("No se especificó ninguna patrulla.");
    }
    
    $patrulla = $controlador->obtenerPatrullaPorId($idPatrulla);
    $integrantes = $controlador->obtenerIntegrantesPorPatrulla($idPatrulla);

} catch (Exception $e) {
    $error = $e->getMessage();
    $patrulla = null;
    $integrantes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Educarama - Perfil de Patrulla</title>

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
    <div class="container contenedor-principal p-4">
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- BARRA SUPERIOR Y DATOS BÁSICOS DE LA PATRULLA -->
        <div class="barra-superior-inst mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="fw-bold mb-1">
                    Patrulla <?= htmlspecialchars($patrulla['nombre'] ?? 'Desconocida') ?>
                </h1>
                <p class="mb-0">
                    <i class="bi bi-diagram-3 me-1"></i> Rama: <?= htmlspecialchars($patrulla['rama'] ?? 'Sin rama') ?>
                </p>
            </div>
            <a href="/Educarama/views/pages/patrullas.php" class="boton-primario px-4 py-2 fw-bold text-decoration-none">
                <i class="bi bi-arrow-left me-2"></i>Volver a patrullas   
            </a>
        </div>

        <!-- NÓMINA DE INTEGRANTES -->
        <div class="tarjeta-blanca-borde p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h4 texto-violeta fw-bold mb-0">Nómina de Integrantes</h2>
            </div>

            <?php if (!empty($integrantes)): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nombre y Apellido</th>
                                <th>Edad</th>
                                <th>Año de Ingreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($integrantes as $integrante): ?>
                                <tr>
                                    <td>
                                        <a href="/Educarama/views/pages/perfil_protagonista.php?id=<?= $integrante['id'] ?>" class="text-decoration-none fw-bold">
                                            <?= htmlspecialchars($integrante['nombre']) ?> <?= htmlspecialchars($integrante['apellido']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($integrante['fecha_nacimiento'])) {
                                            $fechaNacimiento = new DateTime($integrante['fecha_nacimiento']);
                                            $hoy = new DateTime();
                                            $edad = $hoy->diff($fechaNacimiento)->y;
                                            echo $edad . " años";
                                        } else {
                                            echo "<span class='text-muted'>Sin datos</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?= !empty($integrante['anio_ingreso']) ? htmlspecialchars($integrante['anio_ingreso']) : "<span class='text-muted'>Sin datos</span>" ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Esta patrulla aún no tiene protagonistas asignados.</p>
            <?php endif; ?>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="row g-3 mt-3">
            <div class="col-12 col-md-4">
                <a href="asignar_protagonista.php?id_patrulla=<?= htmlspecialchars($idPatrulla) ?>" 
                   class="boton-primario w-100 py-3 fw-bold text-decoration-none text-center d-block">
                   <i class="bi bi-person-plus me-2"></i>Añadir integrante
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