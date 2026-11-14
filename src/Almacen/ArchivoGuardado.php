<?php
// src/Almacen/ArchivoGuardado.php

declare(strict_types=1);

namespace Educarama\Almacen;

/**
 * Datos de un archivo que AlmacenArchivos::guardar() ya dejó en el disco.
 * Es lo que el proyecto guarda en su base para poder enviarlo o borrarlo después.
 */
final class ArchivoGuardado
{
    public function __construct(
        public readonly string $rutaRelativa,    // Dentro del almacén, ej. 'documentos/7/3f9a....pdf'
        public readonly string $nombreOriginal,  // Como se llamaba en la computadora del usuario (saneado)
        public readonly string $tipoMime,        // El real, detectado con finfo
        public readonly int    $tamanioBytes,
    ) {}
}
