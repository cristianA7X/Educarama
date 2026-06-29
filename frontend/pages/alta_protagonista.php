<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educarama - Nuevo Protagonista</title>

    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=1.5">
</head>
<?php
$rama_page = "unidad"; // puede ser "manada", "unidad", "caminante", "rover"
?>

<body class="rama-<?php echo $rama_page; ?>">
    <?php 
    include '../includes/barra_superior.php'; 
    ?>

    <div class="main-content-wrapper without-sidebar >
   
    <main class="main-content-wrapper  without-sidebar">
        <div class="container contenedor-principal my-4 p-4">
            <div class="row mb-3 align-items-center barra-superior-inst mx-0">
                <div class=" align-items-center">
                    <h1 class="h3 fw-bold mb-0">Nuevo Protagonista</h1>
                </div>
              
            </div>

            <div class="row mb-4 align-items-stretch">
                <div class="col-md-12 mb-3 mb-md-0">
                    <div class="tarjeta-blanca-borde p-3 h-100">
                        <hr class="border-white opacity-25">
                        <form>

                            <div class="mb-3">
                                <label for="apellido" class="form-label">Apellido del Protagonista</label>
                                <input type="text" class="form-control" id="apellido" placeholder="Ingrese el/los apellido/s del protagonista">
                            </div>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Protagonista</label>  
                                <input type="text" class="form-control" id="nombre" placeholder="Ingrese el/los nombre/s del protagonista">
                            </div>                            
                            <div class="mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento">
                            </div>
                            <div class="mb-3">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control" id="dni" placeholder="Ingrese el DNI del protagonista">
                            </div>
                            <div class="mb-3">
                                <label for="anio_ingreso" class="form-label">Año de Ingreso</label>
                                <input type="number" class="form-control" id="anio_ingreso" placeholder="Ingrese el año de ingreso">
                            </div>

                            <button type="submit" class="btn btn-primary">Registrar</button>
                        </form>
                    </div>
                </div>
        
            </div>

            <!-- SECCIÓN DE PROGRESIÓN 
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h2 class="h4 texto-violeta fw-bold">Progresión Personal</h2>
                </div>
            </div>
            -->

            <!-- BOTONES DE ACCIÓN 
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <button class="boton-primario w-100 py-3 fw-bold"><i class="bi bi-pencil-square me-2"></i>Registrar Actividad</button>
                </div>
                <div class="col-12 col-md-4">
                    <button class="boton-primario w-100 py-3 fw-bold"><i class="bi bi-clock-history me-2"></i>Ver Historial</button>
                </div>
                <div class="col-12 col-md-4">
                    <button class="boton-primario w-100 py-3 fw-bold"><i class="bi bi-person-gear me-2"></i>Editar Perfil</button>
                </div>
            </div>
            -->
        </div>

    </main>
    </div>
    <?php include '../includes/barra_inferior.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>