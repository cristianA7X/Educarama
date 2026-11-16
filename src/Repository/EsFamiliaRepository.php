<?php // src/Repository/EsFamiliaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EsFamilia;
use PDO;

final class EsFamiliaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idProtagonista, int $idFamiliar): ?EsFamilia
    {
        return $this->buscarUno(
            'SELECT * FROM es_familia WHERE id_protagonista = ? AND id_familiar = ?',
            [$idProtagonista, $idFamiliar],
            EsFamilia::class,
        );
    }

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

    public function listarPorFamiliar(int $idFamiliar): array
    {
        return $this->listar(
            'SELECT * FROM es_familia WHERE id_familiar = ? ORDER BY id_protagonista',
            [$idFamiliar],
            EsFamilia::class,
        );
    }

    public function insertar(EsFamilia $esFamilia): void
    {
        $this->ejecutar(
            'INSERT INTO es_familia (id_protagonista, id_familiar, vinculo, es_contacto_de_emergencia)
             VALUES (:id_protagonista, :id_familiar, :vinculo, :es_contacto_de_emergencia)',
            $this->parametros($esFamilia),
        );
    }

    public function actualizar(EsFamilia $esFamilia): void
    {
        $this->ejecutar(
            'UPDATE es_familia
                SET vinculo = :vinculo, es_contacto_de_emergencia = :es_contacto_de_emergencia
              WHERE id_protagonista = :id_protagonista AND id_familiar = :id_familiar',
            $this->parametros($esFamilia),
        );
    }

    public function eliminar(int $idProtagonista, int $idFamiliar): void
    {
        $this->ejecutar(
            'DELETE FROM es_familia WHERE id_protagonista = ? AND id_familiar = ?',
            [$idProtagonista, $idFamiliar],
        );
    }

    private function parametros(EsFamilia $esFamilia): array
    {
        return [
            'id_protagonista'           => $esFamilia->idProtagonista,
            'id_familiar'               => $esFamilia->idFamiliar,
            'vinculo'                   => $esFamilia->vinculo,
            'es_contacto_de_emergencia' => (int) $esFamilia->esContactoDeEmergencia,
        ];
    }
}