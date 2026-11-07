<?php
// public/api.php
//
// Punto de entrada de todos los pedidos por fetch: api.php?accion=usuario.iniciarSesion
// Solo responde las acciones de esta lista (ver Educarama\Controller\Api).
// Para sumar una acción: agregar una línea acá con su método HTTP, Controller y método.

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use Educarama\Controller\AccesoController;
use Educarama\Controller\Api;
use Educarama\Controller\DocumentoController;
use Educarama\Controller\EventoController;
use Educarama\Controller\FamiliarController;
use Educarama\Controller\PatrullaController;
use Educarama\Controller\ProgresionController;
use Educarama\Controller\ProtagonistaController;
use Educarama\Controller\SolicitudAltaController;
use Educarama\Controller\UsuarioController;

const ACCIONES = [
    // Cuentas
    'usuario.iniciarSesion'      => ['POST', UsuarioController::class, 'iniciarSesion'],
    'usuario.registrar'          => ['POST', UsuarioController::class, 'registrar'],
    'usuario.cerrarSesion'       => ['POST', UsuarioController::class, 'cerrarSesion'],
    'usuario.cambiarContrasenia' => ['POST', UsuarioController::class, 'cambiarContrasenia'],

    // Progresión (guardado inmediato: un clic = un pedido)
    'progresion.avanzarCompetencia'     => ['POST', ProgresionController::class, 'avanzarCompetencia'],
    'progresion.retrocederCompetencia'  => ['POST', ProgresionController::class, 'retrocederCompetencia'],
    'progresion.marcarAccion'           => ['POST', ProgresionController::class, 'marcarAccion'],
    'progresion.desmarcarAccion'        => ['POST', ProgresionController::class, 'desmarcarAccion'],
    'progresion.marcarTarea'            => ['POST', ProgresionController::class, 'marcarTarea'],
    'progresion.desmarcarTarea'         => ['POST', ProgresionController::class, 'desmarcarTarea'],
    'progresion.confirmarEtapa'         => ['POST', ProgresionController::class, 'confirmarEtapa'],
    'progresion.quitarEtapa'            => ['POST', ProgresionController::class, 'quitarEtapa'],
    'progresion.otorgarInsignia'        => ['POST', ProgresionController::class, 'otorgarInsignia'],
    'progresion.quitarInsignia'         => ['POST', ProgresionController::class, 'quitarInsignia'],
    'progresion.avanzarEspecialidad'    => ['POST', ProgresionController::class, 'avanzarEspecialidad'],
    'progresion.retrocederEspecialidad' => ['POST', ProgresionController::class, 'retrocederEspecialidad'],

    // Protagonistas
    'protagonista.guardar'        => ['POST', ProtagonistaController::class, 'guardar'],
    'protagonista.cambiarSeccion' => ['POST', ProtagonistaController::class, 'cambiarSeccion'],
    'protagonista.eliminar'       => ['POST', ProtagonistaController::class, 'eliminar'],

    // Patrullas (Seisenas, Patrullas, Equipos)
    'patrulla.crear'             => ['POST', PatrullaController::class, 'crear'],
    'patrulla.renombrar'         => ['POST', PatrullaController::class, 'renombrar'],
    'patrulla.eliminar'          => ['POST', PatrullaController::class, 'eliminar'],
    'patrulla.asignarIntegrante' => ['POST', PatrullaController::class, 'asignarIntegrante'],
    'patrulla.quitarIntegrante'  => ['POST', PatrullaController::class, 'quitarIntegrante'],

    // Documentos (la descarga es GET por public/descargar.php)
    'documento.subir'      => ['POST', DocumentoController::class, 'subir'],
    'documento.actualizar' => ['POST', DocumentoController::class, 'actualizar'],
    'documento.eliminar'   => ['POST', DocumentoController::class, 'eliminar'],

    // Familiares (siempre desde un protagonista)
    'familiar.guardar'     => ['POST', FamiliarController::class, 'guardar'],
    'familiar.desvincular' => ['POST', FamiliarController::class, 'desvincular'],

    // Eventos
    'evento.guardar'    => ['POST', EventoController::class, 'guardar'],
    'evento.desactivar' => ['POST', EventoController::class, 'desactivar'],
    'evento.reactivar'  => ['POST', EventoController::class, 'reactivar'],
    'evento.eliminar'   => ['POST', EventoController::class, 'eliminar'],

    // Accesos (gestión de roles de un grupo o una sección)
    'acceso.dar'        => ['POST', AccesoController::class, 'dar'],
    'acceso.cambiarRol' => ['POST', AccesoController::class, 'cambiarRol'],
    'acceso.quitar'     => ['POST', AccesoController::class, 'quitar'],

    // Alta de un grupo (el usuario envía, un admin revisa)
    'solicitud_alta.enviar'   => ['POST', SolicitudAltaController::class, 'enviar'],
    'solicitud_alta.aprobar'  => ['POST', SolicitudAltaController::class, 'aprobar'],
    'solicitud_alta.rechazar' => ['POST', SolicitudAltaController::class, 'rechazar'],
];

(new Api(ACCIONES))->ejecutar();
