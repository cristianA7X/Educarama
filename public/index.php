
<?php
//public/index.php
require_once __DIR__ . '/../src/Controladores/AuthMiddleware.php';
require_once __DIR__ . '/../src/Controladores/eventoControlador.php';

$tituloPagina = "Inicio";
$eventoControlador = new eventoControlador();
// Llamas al método y guardas el resultado en la variable que usa el 
// HTML$eventos = $eventoControlador->listareventosHome()
//
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educarama - Inicio</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Educarama/public/css/styles.css?v=1.5">
</head>

<body>
    <?php
    include __DIR__ . '/../views/pages/includes/barra_superior.php';
    include __DIR__ . '/../views/pages/includes/menu_lateral.php';
    ?>

    <div class="main-content-wrapper">
        <div class="container contenedor-principal  p-4">

            <section class="barra-superior-inst mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="fw-bold mb-1">Bienvenido a Educarama</h1>
                    <p class="mb-0">Panel principal para visualizar eventos, actividades y novedades del grupo scout.</p>
                </div>
                <a href="/Educarama/views/pages/protagonistas_nomina.php" class="boton-primario px-4 py-2 fw-bold text-decoration-none">
                    <i class="me-2"></i>Ver nómina completa   
                </a>
            </section>

            <h3 class="home-section-title">📢 Eventos</h3>

            <section class="home-alerts">
                <?php require __DIR__ . '/../views/pages/includes/alertas.php'; ?>
            </section>

            <section class="home-stats">
                <a class="home-stat-card text-decoration-none" href="/Educarama/views/pages/protagonistas.php">
                    <div class="icon">👦</div>
                    <p>Protagonistas</p>
                </a>

                <a class="home-stat-card text-decoration-none" href="/Educarama/views/pages/patrullas.php">
                    <div class="icon">👥</div>
                    <p>Patrullas</p>
                </a>

                <a class="home-stat-card text-decoration-none" href="/Educarama/views/pages/ramas.php">
                    <div class="icon">🏕️</div>
                    <p>Ramas</p>
                </a>

                <div class="home-stat-card" >
                    <div class="icon">📅</div>
                    <p>Actividades</p>                
                </div>
            </section>

            <section class="home-actions">
                <a href="/Educarama/views/pages/protagonistas.php" class="home-action-btn">Ver protagonistas</a>
                <a href="/Educarama/views/pages/alta_patrulla.php" class="home-action-btn">Nueva patrulla</a>
            </section>

        </div>
    </div>

    <?php include __DIR__ . '/../views/pages/includes/barra_inferior.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>