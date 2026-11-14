<?php
// src/Repository/PatrullaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Patrulla;

/**
 * Lectura y escritura de `patrulla`: los pequeños equipos de una sección
 * (Seisena, Patrulla o Equipo según la rama; el nombre sale de `nomenclatura`).
 * Los integrantes se piden a ProtagonistaRepository::listarPorPatrulla().
 */
final class PatrullaRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Patrulla
    {
        return $this->buscarUno('SELECT * FROM patrulla WHERE id = ?', [$id], Patrulla::class);
    }

    /** @return Patrulla[] Ordenadas por nombre. */
    public function listarPorSeccion(int $idSeccion): array
    {
        return $this->listar(
            'SELECT * FROM patrulla WHERE id_seccion = ? ORDER BY nombre',
            [$idSeccion],
            Patrulla::class,
        );
    }

    /**
     * La patrulla de la sección con ese nombre, o null. Compara con la
     * intercalación de la base (utf8mb4_spanish_ci): no distingue mayúsculas
     * ni acentos, así "Ñandú" y "ñandu" cuentan como el mismo nombre.
     */
    public function buscarPorNombre(int $idSeccion, string $nombre): ?Patrulla
    {
        return $this->buscarUno(
            'SELECT * FROM patrulla WHERE id_seccion = ? AND nombre = ?',
            [$idSeccion, $nombre],
            Patrulla::class,
        );
    }

    /** Guarda una patrulla nueva, le completa el id y lo devuelve. */
    public function insertar(Patrulla $patrulla): int
    {
        $patrulla->id = $this->insertarFila(
            'INSERT INTO patrulla (nombre, id_seccion) VALUES (:nombre, :id_seccion)',
            ['nombre' => $patrulla->nombre, 'id_seccion' => $patrulla->idSeccion],
        );

        return $patrulla->id;
    }

    public function actualizar(Patrulla $patrulla): void
    {
        $this->ejecutar(
            'UPDATE patrulla SET nombre = :nombre, id_seccion = :id_seccion WHERE id = :id',
            ['nombre' => $patrulla->nombre, 'id_seccion' => $patrulla->idSeccion, 'id' => $this->exigirId($patrulla->id)],
        );
    }

    /**
     * Borra la patrulla. Sus integrantes NO se borran: quedan sin patrulla
     * (la base pone protagonista.id_patrulla en NULL por ON DELETE SET NULL).
     */
    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM patrulla WHERE id = ?', [$id]);
    }
}
