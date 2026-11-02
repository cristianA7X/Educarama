<?php
// src/Repository/InsigniaOtorgadaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\InsigniaOtorgada;

/**
 * Lectura y escritura de `insignia_otorgada`: toda insignia entregada a un
 * protagonista, incluidas las de etapa.
 *
 * Tabla de progresión: sin fila = no otorgada. La fila la crea un educador al confirmar.
 * Sin id propio: cada fila se identifica por id_protagonista + id_insignia.
 */
final class InsigniaOtorgadaRepository extends RepositorioBase
{
    public function buscar(int $idProtagonista, int $idInsignia): ?InsigniaOtorgada
    {
        return $this->buscarUno(
            'SELECT * FROM insignia_otorgada WHERE id_protagonista = ? AND id_insignia = ?',
            [$idProtagonista, $idInsignia],
            InsigniaOtorgada::class,
        );
    }

    /** @return InsigniaOtorgada[] En el orden en que se entregaron. */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT * FROM insignia_otorgada WHERE id_protagonista = ? ORDER BY fecha, id_insignia',
            [$idProtagonista],
            InsigniaOtorgada::class,
        );
    }

    /** Registra la entrega, en la fecha que trae el objeto. */
    public function insertar(InsigniaOtorgada $insigniaOtorgada): void
    {
        $this->ejecutar(
            'INSERT INTO insignia_otorgada (id_protagonista, id_insignia, fecha) VALUES (?, ?, ?)',
            [$insigniaOtorgada->idProtagonista, $insigniaOtorgada->idInsignia, $insigniaOtorgada->fecha],
        );
    }

    /** Borra la fila: la insignia vuelve a quedar como no otorgada. */
    public function eliminar(int $idProtagonista, int $idInsignia): void
    {
        $this->ejecutar(
            'DELETE FROM insignia_otorgada WHERE id_protagonista = ? AND id_insignia = ?',
            [$idProtagonista, $idInsignia],
        );
    }
}
