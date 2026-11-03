<?php
// src/Service/Excepcion/NoEncontradoException.php

declare(strict_types=1);

namespace Educarama\Service\Excepcion;

/**
 * Se pidió algo que no existe (ej. un id de protagonista que no está en la base).
 * El Controller suele responder con un 404.
 */
final class NoEncontradoException extends ServicioException
{
}
