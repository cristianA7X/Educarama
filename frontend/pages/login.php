<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educarama - Perfil de Protagonista</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Ubuntu -->
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/styles.css?v=1.5">
</head>


<body>
    <?php 
    // Componentes fijos de la interfaz
    include '../includes/barra_superior.php'; 
    ?>

    <!-- CONTENEDOR MAESTRO -->
    <div class="main-content-wrapper">
        
        <div class="container contenedor-principal my-4 p-4">
            
            <!-- ENCABEZADO DEL PERFIL -->
            <div class="row mb-3 align-items-center barra-superior-inst mx-0">
                <div class=" align-items-center">
                    <h1 class="h3 fw-bold mb-0">Iniciar Sesión</h1>
                </div>
              
            </div>

            <!-- INFORMACIÓN BÁSICA -->
            <div class="row mb-4 align-items-stretch">
                <div class="col-md-12 mb-3 mb-md-0">
                    <div class="tarjeta-blanca-borde p-3 h-100">
                        <hr class="border-white opacity-25">
                        <form>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" placeholder="nombre@ejemplo.com">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" placeholder="Ingrese su contraseña">
                            </div>
                            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                        </form>
                    </div>
                </div>
        
            </div>

            <!-- SECCIÓN DE PROGRESIÓN -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h2 class="h4 texto-violeta fw-bold">Progresión Personal</h2>
                </div>

            


            </div>

            <!-- BOTONES DE ACCIÓN -->
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
            
        </div>
    </div>

    <?php include '../includes/barra_inferior.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>