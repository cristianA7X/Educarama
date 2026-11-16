<?php // src/Repository/DocumentoRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Documento;
use PDO;

final class DocumentoRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Documento
    {
        return $this->buscarUno('SELECT * FROM documento WHERE id = ?', [$id], Documento::class);
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT * FROM documento WHERE id_protagonista = ? ORDER BY fecha_subida DESC, id DESC',
            [$idProtagonista],
            Documento::class,
        );
    }

    public function insertar(Documento $documento): int
    {
        $documento->id = $this->insertarFila(
            'INSERT INTO documento (id_protagonista, tipo_documento, descripcion, ruta_archivo,
                                    nombre_original, tipo_mime, tamanio_bytes)
             VALUES (:id_protagonista, :tipo_documento, :descripcion, :ruta_archivo,
                     :nombre_original, :tipo_mime, :tamanio_bytes)',
            [
                'id_protagonista' => $documento->idProtagonista,
                'tipo_documento'  => $documento->tipoDocumento,
                'descripcion'     => $documento->descripcion,
                'ruta_archivo'    => $documento->rutaArchivo,
                'nombre_original' => $documento->nombreOriginal,
                'tipo_mime'       => $documento->tipoMime,
                'tamanio_bytes'   => $documento->tamanioBytes,
            ],
        );
        return $documento->id;
    }

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

    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM documento WHERE id = ?', [$id]);
    }
}