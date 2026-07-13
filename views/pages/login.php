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
    <link rel="stylesheet" href="public/css/styles.css?v=1.5">
</head>


<?php
$rama_page = ""; // puede ser "manada", "unidad", "caminante", "rover"
?>

<body class="rama-<?php echo $rama_page; ?>">
    <?php 
    // Componentes fijos de la interfaz
    include 'includes/barra_superior.php'; 
    ?>

    <!-- CONTENEDOR MAESTRO -->
    <div class="main-content-wrapper without-sidebar">
        
        <div class="container contenedor-principal without-sidebar my-4 p-4">
            
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

                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="nombre@ejemplo.com">
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

            
            
            
        </div>
    </div>

    <?php include 'includes/barra_inferior.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            const email = document.getElementById('email').value;
            const contrasenia = document.getElementById('password').value;

            try {
                const response = await fetch('http://localhost/Educarama/src/Controladores/AuthController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, contrasenia })
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message);
                    // Redirigir a otra página si es necesario
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al intentar iniciar sesión.');
            }
        });

        // Redirigir al home después de 1.5 segundos
            setTimeout(() => {
            
                window.location.href = 'home.php'; 
            }, 1500);

</body>
</html>