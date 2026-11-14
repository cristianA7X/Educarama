<?php
// src/Service/Excepcion/AccesoDenegadoException.php

declare(strict_types=1);

namespace Educarama\Service\Excepcion;

/**
 * El usuario no tiene el rol necesario para la operación.
 * La lanza PermisoService::exigir(); el Controller suele responder con un 403.
 */
final class AccesoDenegadoException extends ServicioException
{
}
