<?php
// src/Repository/EsFamiliaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EsFamilia;

/**
 * Lectura y escritura de `es_familia`: el vínculo entre un protagonista y un familiar.
 * No tiene id propio: cada fila se identifica por id_protagonista + id_familiar.
 */
final class EsFamiliaRepository extends RepositorioBase
{
    public function buscar(int $idProtagonista, int $idFamiliar): ?EsFamilia
    {
        return $this->buscarUno(
            'SELECT * FROM es_familia WHERE id_protagonista = ? AND id_familiar = ?',
            [$idProtagonista, $idFamiliar],
            EsFamilia::class,
        );
    }

    /**
     * Los vínculos de un protagonista, en el mismo orden que
     * FamiliarRepository::listarPorProtagonista (primero los contactos de emergencia).
     *
     * @return EsFamilia[]
     */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT * FROM es_familia
              WHERE id_protagonista = ?
              ORDER BY es_contacto_de_emergencia DESC, id_familiar',
            [$idProtagonista],
            EsFamilia::class,
        );
    }

    /**
     * Los vínculos de un familiar (por ejemplo, una madre con dos hijos en el grupo).
     *
     * @return EsFamilia[]
     */
    public function listarPorFamiliar(int $idFamiliar): array
    {
        return $this->listar(
            'SELECT * FROM es_familia WHERE id_familiar = ? ORDER BY id_protagonista',
            [$idFamiliar],
            EsFamilia::class,
        );
    }

    /** Vincula un familiar con un protagonista. Si el vínculo ya existe, la base lanza una excepción (PK duplicada). */
    public function insertar(EsFamilia $esFamilia): void
    {
        $this->ejecutar(
            'INSERT INTO es_familia (id_protagonista, id_familiar, vinculo, es_contacto_de_emergencia)
             VALUES (:id_protagonista, :id_familiar, :vinculo, :es_contacto_de_emergencia)',
            $this->parametros($esFamilia),
        );
    }

    /** Cambia el vínculo o el contacto de emergencia. Los dos ids identifican la fila: no se modifican. */
    public function actualizar(EsFamilia $esFamilia): void
    {
        $this->ejecutar(
            'UPDATE es_familia
                SET vinculo = :vinculo, es_contacto_de_emergencia = :es_contacto_de_emergencia
              WHERE id_protagonista = :id_protagonista AND id_familiar = :id_familiar',
            $this->parametros($esFamilia),
        );
    }

    /** Desvincula: el protagonista y el familiar siguen existiendo. */
    public function eliminar(int $idProtagonista, int $idFamiliar): void
    {
        $this->ejecutar(
            'DELETE FROM es_familia WHERE id_protagonista = ? AND id_familiar = ?',
            [$idProtagonista, $idFamiliar],
        );
    }

    /** @return array<string, mixed> */
    private function parametros(EsFamilia $esFamilia): array
    {
        return [
            'id_protagonista'           => $esFamilia->idProtagonista,
            'id_familiar'               => $esFamilia->idFamiliar,
            'vinculo'                   => $esFamilia->vinculo,
            'es_contacto_de_emergencia' => (int) $esFamilia->esContactoDeEmergencia,  // bool → TINYINT
        ];
    }
}
