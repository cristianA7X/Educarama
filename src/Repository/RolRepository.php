<?php
// src/Repository/RolRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Rol;

/**
 * Lectura de `rol`: Supervisor (1), Ayudante (2), Responsable (3) y Jefe (4).
 * Tabla estática: solo lectura.
 */
final class RolRepository extends RepositorioEstatico
{
    /** @return Rol[] De menor a mayor nivel. */
    public function listarTodos(): array
    {
        return $this->listar('SELECT * FROM rol ORDER BY nivel', [], Rol::class);
    }

    public function buscarPorId(int $id): ?Rol
    {
        return $this->buscarUno('SELECT * FROM rol WHERE id = ?', [$id], Rol::class);
    }

    /** Ej.: buscarPorNivel(NivelRol::Jefe) al aprobar un alta, para asignar el Jefe del grupo. */
    public function buscarPorNivel(NivelRol $nivel): ?Rol
    {
        return $this->buscarUno('SELECT * FROM rol WHERE nivel = ?', [$nivel->value], Rol::class);
    }
}
