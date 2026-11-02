<?php
// src/Model/Documento.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Archivo adjunto de un protagonista (la subida se implementa más adelante).
 *
 * Tabla `documento`. Tabla dinámica: la cargan los usuarios.
 */
final class Documento
{
    public function __construct(
        public ?int    $id,              // null mientras no se guardó en la base
        public int     $idProtagonista,
        public ?string $tipoDocumento,
        public ?string $descripcion,
        public string  $rutaArchivo,
        public string  $nombreOriginal,
        public ?string $fechaSubida,     // La pone la base al insertar
    ) {}

    /**
     * Arma el objeto desde una fila de `documento`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idProtagonista: (int) $fila['id_protagonista'],
            tipoDocumento: $fila['tipo_documento'] === null ? null : (string) $fila['tipo_documento'],
            descripcion: $fila['descripcion'] === null ? null : (string) $fila['descripcion'],
            rutaArchivo: (string) $fila['ruta_archivo'],
            nombreOriginal: (string) $fila['nombre_original'],
            fechaSubida: $fila['fecha_subida'] === null ? null : (string) $fila['fecha_subida'],
        );
    }
}
