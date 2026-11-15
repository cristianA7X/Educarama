<?php
// src/Service/Excepcion/ServicioException.php

declare(strict_types=1);

namespace Educarama\Service\Excepcion;

/**
 * Base de las excepciones que lanzan los Services.
 *
 * Su mensaje está pensado para mostrarse al usuario tal cual. Así el Controller
 * puede atrapar todas juntas:
 *
 *   try { ... } catch (ServicioException $e) { $error = $e->getMessage(); }
 *
 * Una PDOException u otro error de PHP NO es una ServicioException: es un error
 * del sistema y su mensaje no se le muestra al usuario.
 */
abstract class ServicioException extends \RuntimeException
{
}
