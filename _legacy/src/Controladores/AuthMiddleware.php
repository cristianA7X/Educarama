<?php
// src/Controladores/AuthMiddleware.php
// Este middleware se encarga de verificar si un usuario está autenticado 
// antes de permitir el acceso a ciertas rutas o recursos protegidos.

// Asegura de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica si NO existe el ID de usuario en la sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /Educarama/views/pages/login.php");
    exit; 
}

?>