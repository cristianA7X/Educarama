<?php
// src/Service/Excepcion/SesionRequeridaException.php

declare(strict_types=1);

namespace Educarama\Service\Excepcion;

/**
 * La página necesita un usuario logueado y no hay sesión (o el usuario ya no existe).
 * La lanza UsuarioService::exigirSesion(); el Controller redirige al login.
 */
final class SesionRequeridaException extends ServicioException
{
}
