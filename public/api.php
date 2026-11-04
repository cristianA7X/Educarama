<?php
// public/api.php
//
// Punto de entrada de todos los pedidos por fetch: api.php?accion=usuario.iniciarSesion
// Solo responde las acciones de esta lista (ver Educarama\Controller\Api).
// Para sumar una acción: agregar una línea acá con su método HTTP, Controller y método.

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use Educarama\Controller\Api;
use Educarama\Controller\UsuarioController;

const ACCIONES = [
    // Cuentas
    'usuario.iniciarSesion'      => ['POST', UsuarioController::class, 'iniciarSesion'],
    'usuario.registrar'          => ['POST', UsuarioController::class, 'registrar'],
    'usuario.cerrarSesion'       => ['POST', UsuarioController::class, 'cerrarSesion'],
    'usuario.cambiarContrasenia' => ['POST', UsuarioController::class, 'cambiarContrasenia'],
];

(new Api(ACCIONES))->ejecutar();
