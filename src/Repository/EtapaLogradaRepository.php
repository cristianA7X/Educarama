<?php
// src/Repository/EtapaLogradaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EtapaLograda;

/**
 * Lectura y escritura de `etapa_lograda`: las etapas que un protagonista logró.
 * La etapa actual es la última lograda (ver buscarActual).
 *
 * Tabla de progresión: sin fila = no lograda. La fila la crea un educador al confirmar;
 * ofrecer la insignia de la etapa en la misma operación lo hace el Service.
 * Sin id propio: cada fila se identifica por id_protagonista + id_etapa.
 */
final class EtapaLogradaRepository extends RepositorioBase
{
    public function buscar(int $idProtagonista, int $idEtapa): ?EtapaLograda
    {
        return $this->buscarUno(
            'SELECT * FROM etapa_lograda WHERE id_protagonista = ? AND id_etapa = ?',
            [$idProtagonista, $idEtapa],
            EtapaLograda::class,
        );
    }

    /**
     * Todas las etapas logradas, de todas las ramas por las que pasó el protagonista,
     * ordenadas por rama y número (su historial).
     *
     * @return EtapaLograda[]
     */
    public function listarPorProtagonista(int $idProtagonista): array
    {
        // El JOIN es solo para ordenar: se devuelven únicamente las columnas de etapa_lograda.
        return $this->listar(
            'SELECT el.*
               FROM etapa_lograda el
               JOIN etapa e ON e.id = el.id_etapa
              WHERE el.id_protagonista = ?
              ORDER BY e.id_rama, e.numero',
            [$idProtagonista],
            EtapaLograda::class,
        );
    }

    /**
     * La etapa actual: la de mayor número lograda DENTRO de la rama indicada
     * (la de la sección actual del protagonista). Null si todavía no logró ninguna.
     *
     * Se ordena por número de etapa y no por fecha, porque la fecha puede
     * cargarse hacia atrás. Y se filtra por rama porque, si pasó de Scouts a
     * Caminantes, sus etapas de Scouts siguen guardadas.
     */
    public function buscarActual(int $idProtagonista, int $idRama): ?EtapaLograda
    {
        return $this->buscarUno(
            'SELECT el.*
               FROM etapa_lograda el
               JOIN etapa e ON e.id = el.id_etapa
              WHERE el.id_protagonista = ? AND e.id_rama = ?
              ORDER BY e.numero DESC
              LIMIT 1',
            [$idProtagonista, $idRama],
            EtapaLograda::class,
        );
    }

    /** Registra que el protagonista logró la etapa, en la fecha que trae el objeto. */
    public function insertar(EtapaLograda $etapaLograda): void
    {
        $this->ejecutar(
            'INSERT INTO etapa_lograda (id_protagonista, id_etapa, elemento, fecha)
             VALUES (:id_protagonista, :id_etapa, :elemento, :fecha)',
            $this->parametros($etapaLograda),
        );
    }

    /** Cambia el elemento (solo Caminantes) y la fecha. */
    public function actualizar(EtapaLograda $etapaLograda): void
    {
        $this->ejecutar(
            'UPDATE etapa_lograda
                SET elemento = :elemento, fecha = :fecha
              WHERE id_protagonista = :id_protagonista AND id_etapa = :id_etapa',
            $this->parametros($etapaLograda),
        );
    }

    /** Borra la fila: la etapa vuelve a quedar como no lograda. La insignia otorgada NO se borra sola. */
    public function eliminar(int $idProtagonista, int $idEtapa): void
    {
        $this->ejecutar(
            'DELETE FROM etapa_lograda WHERE id_protagonista = ? AND id_etapa = ?',
            [$idProtagonista, $idEtapa],
        );
    }

    /** @return array<string, mixed> */
    private function parametros(EtapaLograda $etapaLograda): array
    {
        return [
            'id_protagonista' => $etapaLograda->idProtagonista,
            'id_etapa'        => $etapaLograda->idEtapa,
            'elemento'        => $etapaLograda->elemento?->value,  // enum → texto, o null
            'fecha'           => $etapaLograda->fecha,
        ];
    }
}
