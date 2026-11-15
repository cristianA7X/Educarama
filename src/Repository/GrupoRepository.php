<?php
// src/Repository/GrupoRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Grupo;

/**
 * Lectura y escritura de `grupo`.
 * Los grupos se crean al aprobar una solicitud de alta (lo hace el Service).
 * No tiene eliminar: borrar un grupo se resuelve cuando exista la baja lógica.
 */
final class GrupoRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Grupo
    {
        return $this->buscarUno('SELECT * FROM grupo WHERE id = ?', [$id], Grupo::class);
    }

    /** El código es el número oficial del grupo y es único (ej. '64'). */
    public function buscarPorCodigo(string $codigo): ?Grupo
    {
        return $this->buscarUno('SELECT * FROM grupo WHERE codigo = ?', [$codigo], Grupo::class);
    }

    /** Guarda un grupo nuevo, le completa el id y lo devuelve. */
    public function insertar(Grupo $grupo): int
    {
        $grupo->id = $this->insertarFila(
            'INSERT INTO grupo (codigo, nombre) VALUES (:codigo, :nombre)',
            ['codigo' => $grupo->codigo, 'nombre' => $grupo->nombre],
        );

        return $grupo->id;
    }

    public function actualizar(Grupo $grupo): void
    {
        $this->ejecutar(
            'UPDATE grupo SET codigo = :codigo, nombre = :nombre WHERE id = :id',
            ['codigo' => $grupo->codigo, 'nombre' => $grupo->nombre, 'id' => $this->exigirId($grupo->id)],
        );
    }
}
