<?php
// src/Repository/DocumentoRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Documento;

/**
 * Lectura y escritura de `documento`: los archivos adjuntos de un protagonista.
 *
 * Este repositorio solo maneja la fila de la base. Subir, mover o borrar el
 * archivo del disco es tarea del Service (la subida se implementa más adelante).
 */
final class DocumentoRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Documento
    {
        return $this->buscarUno('SELECT * FROM documento WHERE id = ?', [$id], Documento::class);
    }

    /** @return Documento[] Del más reciente al más viejo. */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT * FROM documento WHERE id_protagonista = ? ORDER BY fecha_subida DESC, id DESC',
            [$idProtagonista],
            Documento::class,
        );
    }

    /**
     * Guarda un documento nuevo, le completa el id y lo devuelve.
     * fecha_subida la pone la base: en el objeto sigue en null hasta volver a leerlo.
     */
    public function insertar(Documento $documento): int
    {
        $documento->id = $this->insertarFila(
            'INSERT INTO documento (id_protagonista, tipo_documento, descripcion, ruta_archivo, nombre_original)
             VALUES (:id_protagonista, :tipo_documento, :descripcion, :ruta_archivo, :nombre_original)',
            [
                'id_protagonista' => $documento->idProtagonista,
                'tipo_documento'  => $documento->tipoDocumento,
                'descripcion'     => $documento->descripcion,
                'ruta_archivo'    => $documento->rutaArchivo,
                'nombre_original' => $documento->nombreOriginal,
            ],
        );

        return $documento->id;
    }

    /** Solo cambia el tipo y la descripción: el archivo y su fecha no se modifican. */
    public function actualizar(Documento $documento): void
    {
        $this->ejecutar(
            'UPDATE documento SET tipo_documento = :tipo_documento, descripcion = :descripcion WHERE id = :id',
            [
                'tipo_documento' => $documento->tipoDocumento,
                'descripcion'    => $documento->descripcion,
                'id'             => $this->exigirId($documento->id),
            ],
        );
    }

    /** Borra la fila. El archivo del disco lo borra el Service. */
    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM documento WHERE id = ?', [$id]);
    }
}
