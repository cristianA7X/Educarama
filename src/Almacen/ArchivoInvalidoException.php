<?php
// src/Almacen/ArchivoInvalidoException.php

declare(strict_types=1);

namespace Educarama\Almacen;

/**
 * El archivo subido no se puede guardar: no llegó, es muy grande o su tipo no
 * está permitido. El mensaje se puede mostrar al usuario tal cual: nunca
 * incluye rutas del disco ni detalles internos.
 *
 * Los errores del sistema (no se pudo crear la carpeta, disco lleno) NO son
 * esta excepción: son \RuntimeException, y su mensaje no se le muestra al usuario.
 */
final class ArchivoInvalidoException extends \RuntimeException
{
}
