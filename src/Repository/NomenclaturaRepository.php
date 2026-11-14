<?php
// src/Repository/NomenclaturaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Nomenclatura;

/**
 * Lectura de `nomenclatura`: términos que cambian según la rama.
 * Claves cargadas: 'areas', 'competencias', 'pequenio_equipo'.
 * Tabla estática: solo lectura.
 *
 *   $repo->buscar('areas', 1)?->texto;          // 'Senderos'
 *   $repo->listarPorRama(4)['areas']->texto;    // 'Áreas de crecimiento'
 */
final class NomenclaturaRepository extends RepositorioEstatico
{
    public function buscar(string $clave, int $idRama): ?Nomenclatura
    {
        return $this->buscarUno(
            'SELECT * FROM nomenclatura WHERE clave = ? AND id_rama = ?',
            [$clave, $idRama],
            Nomenclatura::class,
        );
    }

    /**
     * Todos los términos de una rama, indexados por clave.
     *
     * @return array<string, Nomenclatura>
     */
    public function listarPorRama(int $idRama): array
    {
        $lista = $this->listar(
            'SELECT * FROM nomenclatura WHERE id_rama = ? ORDER BY clave',
            [$idRama],
            Nomenclatura::class,
        );

        // array_column con null devuelve el objeto entero, usando 'clave' como índice.
        return array_column($lista, null, 'clave');
    }
}
