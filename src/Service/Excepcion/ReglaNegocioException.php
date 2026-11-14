<?php
// src/Service/Excepcion/ReglaNegocioException.php

declare(strict_types=1);

namespace Educarama\Service\Excepcion;

/**
 * La operación viola una regla de negocio.
 * Ej.: asignar una patrulla de otra sección, o cargar acciones en Rovers.
 */
final class ReglaNegocioException extends ServicioException
{
}
