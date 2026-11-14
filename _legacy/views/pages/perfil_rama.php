<?php
require_once __DIR__ . '/../../src/Controladores/AuthMiddleware.php';
require_once __DIR__ . '/../../src/Controladores/RamaController.php';
require_once __DIR__ . '/../../src/Helpers/RamaHelper.php';


$tituloPagina = "Perfil de Rama";
$controlador = new RamaController();
$rama = $controlador->obtenerPerfilDesdeUrl();

if (!$rama) {
    header("Location: ramas.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educarama - Perfil de Rama</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Ubuntu -->
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="/Educarama/public/css/styles.css?v=1.5">
</head>


<body class="<?= claseRama($patrulla['id_categoria_rama'] ?? null) ?>">    
    <?php 
    // Componentes fijos de la interfaz
    include __DIR__ . '/includes/barra_superior.php'; 
    include __DIR__ . '/includes/menu_lateral.php'; 
    ?>

    <!-- CONTENEDOR MAESTRO -->
    <div class="main-content-wrapper">
        
        <div class="container contenedor-principal p-4">
            <?php if (isset($_GET['actualizado']) && $_GET['actualizado'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        Los datos de la rama se modificaron correctamente.

        <button 
            type="button" 
            class="btn-close" 
            data-bs-dismiss="alert" 
            aria-label="Cerrar">
        </button>
    </div>
<?php endif; ?>
            
            <!-- ENCABEZADO DEL PERFIL -->
            <div class="barra-superior-inst mb-3 row  
            align-items-center Smx-0">
                <div class="col-8">
                    <h1 class="h3 fw-bold mb-0">
                        <?= htmlspecialchars($rama['nombre']) ?>
                    </h1>
       
                </div>

                <div class="col-4 d-flex justify-content-end">
                    <div class="circulo-logo">
                        <img 
                            class="img-fluid rounded-circle" 
                            src="../assets/images/RamaLogoLyL.png" 
                            alt="Logo rama"
                        >
                    </div>
                </div>

                
            </div>

<!-- INFORMACIÓN BÁSICA -->
<div class="row mb-4 align-items-stretch">
    <div class="col-md-12 mb-3 mb-md-0">
        <div class="tarjeta-blanca-borde p-4 h-100">

            <p>
                <i class="bi bi-info-circle me-2"></i>
                Descripción: <?= htmlspecialchars($rama['descripcion'] ?? 'Por Agregar') ?>
            </p>
            <p>
                <i class="bi bi-tag me-2"></i>
                Categoria Rama: <?= htmlspecialchars($rama['categoria_rama'] ?? 'Sin categoría') ?>
            </p>
            <p >
                <i class="bi bi-diagram-3 me-2"></i>
                Rama: <?= htmlspecialchars($rama['nombre'] ?? 'Sin rama') ?>
            </p>
            <p>
                <i class="bi bi-people me-2"></i>
                Grupo: <?= htmlspecialchars($rama['grupo'] ?? 'Sin grupo') ?>
            </p>


    

        </div>
    </div>
</div>


            <!-- BOTONES DE ACCIÓN -->
            <div class="row g-3">

          
                <div class="col-12 col-md-4">
                    <a 
                        href="alta_rama.php?id=<?= $rama['id'] ?>" 
                        class="boton-primario w-100 py-3 fw-bold text-decoration-none text-center d-block">
                        <i class="bi bi-person-gear me-2"></i>Editar Rama
                        </a>
                </div>
            </div>
            
        </div>
    </div>

    <?php include __DIR__ . '/includes/barra_inferior.php'; ?>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    setTimeout(() => {
        const alerta = document.querySelector('.alert-success');

        if (alerta) {
            const alertaBootstrap = bootstrap.Alert.getOrCreateInstance(alerta);
            alertaBootstrap.close();
        }
    }, 3000);
</script>

</body>
</body>
</html>