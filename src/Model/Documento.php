<?php
// src/Model/Documento.php

declare(strict_types=1);

namespace Educarama\Model;

/**
 * Archivo adjunto de un protagonista (ficha médica, autorización...).
 *
 * Tabla `documento`. Tabla dinámica: la cargan los usuarios.
 * El archivo en sí está en el almacén (Educarama\Almacen); acá solo se anota
 * dónde quedó y sus datos. Lo sube y lo borra DocumentoService.
 */
final class Documento
{
    public function __construct(
        public ?int    $id,              // null mientras no se guardó en la base
        public int     $idProtagonista,
        public ?string $tipoDocumento,   // Texto libre, ej. 'Ficha médica'
        public ?string $descripcion,
        public string  $rutaArchivo,     // Relativa al almacén; nunca se muestra al usuario
        public string  $nombreOriginal,  // Con el que se descarga
        public string  $tipoMime,        // Real, detectado por contenido
        public int     $tamanioBytes,
        public ?string $fechaSubida,     // La pone la base al insertar ('AAAA-MM-DD HH:MM:SS')
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
            tipoMime: (string) $fila['tipo_mime'],
            tamanioBytes: (int) $fila['tamanio_bytes'],
            fechaSubida: $fila['fecha_subida'] === null ? null : (string) $fila['fecha_subida'],
        );
    }
}
