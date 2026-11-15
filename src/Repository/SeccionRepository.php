<?php
// src/Repository/SeccionRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Seccion;

/**
 * Lectura y escritura de `seccion`: la Manada, Unidad, etc. de un grupo concreto.
 * (En el legacy esto era la tabla `rama`.)
 * No tiene eliminar: se resuelve cuando exista la baja lógica.
 */
final class SeccionRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Seccion
    {
        return $this->buscarUno('SELECT * FROM seccion WHERE id = ?', [$id], Seccion::class);
    }

    /** @return Seccion[] Ordenadas por rama (LyL → Rovers) y nombre. */
    public function listarPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT * FROM seccion WHERE id_grupo = ? ORDER BY id_rama, nombre',
            [$idGrupo],
            Seccion::class,
        );
    }

    /** Guarda una sección nueva, le completa el id y lo devuelve. */
    public function insertar(Seccion $seccion): int
    {
        $seccion->id = $this->insertarFila(
            'INSERT INTO seccion (nombre, id_grupo, id_rama) VALUES (:nombre, :id_grupo, :id_rama)',
            ['nombre' => $seccion->nombre, 'id_grupo' => $seccion->idGrupo, 'id_rama' => $seccion->idRama],
        );

        return $seccion->id;
    }

    public function actualizar(Seccion $seccion): void
    {
        $this->ejecutar(
            'UPDATE seccion SET nombre = :nombre, id_grupo = :id_grupo, id_rama = :id_rama WHERE id = :id',
            [
                'nombre'   => $seccion->nombre,
                'id_grupo' => $seccion->idGrupo,
                'id_rama'  => $seccion->idRama,
                'id'       => $this->exigirId($seccion->id),
            ],
        );
    }
}
