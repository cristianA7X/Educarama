<?php
// src/Repository/FamiliarRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Familiar;

/**
 * Lectura y escritura de `familiar`: los adultos responsables de los protagonistas.
 * Sus datos personales están en `persona` (PersonaRepository, con $familiar->idPersona).
 * El vínculo con cada protagonista (madre, tío...) está en `es_familia` (EsFamiliaRepository).
 */
final class FamiliarRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Familiar
    {
        return $this->buscarUno('SELECT * FROM familiar WHERE id = ?', [$id], Familiar::class);
    }

    /**
     * El familiar asociado a una persona, o null si esa persona no es familiar de nadie.
     * Sirve al cargar un familiar cuyo DNI ya existe: se reutiliza en vez de duplicarlo.
     */
    public function buscarPorPersona(int $idPersona): ?Familiar
    {
        return $this->buscarUno('SELECT * FROM familiar WHERE id_persona = ?', [$idPersona], Familiar::class);
    }

    /**
     * Los familiares de un protagonista. El vínculo y si es contacto de emergencia
     * se piden a EsFamiliaRepository::listarPorProtagonista().
     *
     * @return Familiar[] Primero los contactos de emergencia.
     */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        // El JOIN es solo para filtrar y ordenar: se devuelven únicamente las columnas de familiar.
        return $this->listar(
            'SELECT f.*
               FROM familiar f
               JOIN es_familia ef ON ef.id_familiar = f.id
              WHERE ef.id_protagonista = ?
              ORDER BY ef.es_contacto_de_emergencia DESC, f.id',
            [$idProtagonista],
            Familiar::class,
        );
    }

    /** Guarda un familiar nuevo, le completa el id y lo devuelve. La persona tiene que existir antes. */
    public function insertar(Familiar $familiar): int
    {
        $familiar->id = $this->insertarFila(
            'INSERT INTO familiar (id_persona, telefono_contacto, email_contacto)
             VALUES (:id_persona, :telefono_contacto, :email_contacto)',
            $this->parametros($familiar),
        );

        return $familiar->id;
    }

    public function actualizar(Familiar $familiar): void
    {
        $this->ejecutar(
            'UPDATE familiar
                SET id_persona = :id_persona, telefono_contacto = :telefono_contacto, email_contacto = :email_contacto
              WHERE id = :id',
            $this->parametros($familiar) + ['id' => $this->exigirId($familiar->id)],
        );
    }

    /**
     * Borra el familiar y, en cascada (lo hace la base), sus vínculos en `es_familia`.
     * Su fila en `persona` NO se borra: puede ser además protagonista.
     */
    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM familiar WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed> */
    private function parametros(Familiar $familiar): array
    {
        return [
            'id_persona'        => $familiar->idPersona,
            'telefono_contacto' => $familiar->telefonoContacto,
            'email_contacto'    => $familiar->emailContacto,
        ];
    }
}
