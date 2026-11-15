<?php // src/Repository/EtapaLogradaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EtapaLograda;
use PDO;

final class EtapaLogradaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idProtagonista, int $idEtapa): ?EtapaLograda
    {
        return $this->buscarUno(
            'SELECT * FROM etapa_lograda WHERE id_protagonista = ? AND id_etapa = ?',
            [$idProtagonista, $idEtapa],
            EtapaLograda::class,
        );
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
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

    public function insertar(EtapaLograda $etapaLograda): void
    {
        $this->ejecutar(
            'INSERT INTO etapa_lograda (id_protagonista, id_etapa, elemento, fecha)
             VALUES (:id_protagonista, :id_etapa, :elemento, :fecha)',
            $this->parametros($etapaLograda),
        );
    }

    public function actualizar(EtapaLograda $etapaLograda): void
    {
        $this->ejecutar(
            'UPDATE etapa_lograda
                SET elemento = :elemento, fecha = :fecha
              WHERE id_protagonista = :id_protagonista AND id_etapa = :id_etapa',
            $this->parametros($etapaLograda),
        );
    }

    public function eliminar(int $idProtagonista, int $idEtapa): void
    {
        $this->ejecutar(
            'DELETE FROM etapa_lograda WHERE id_protagonista = ? AND id_etapa = ?',
            [$idProtagonista, $idEtapa],
        );
    }

    private function parametros(EtapaLograda $etapaLograda): array
    {
        return [
            'id_protagonista' => $etapaLograda->idProtagonista,
            'id_etapa'        => $etapaLograda->idEtapa,
            'elemento'        => $etapaLograda->elemento?->value,
            'fecha'           => $etapaLograda->fecha,
        ];
    }
}