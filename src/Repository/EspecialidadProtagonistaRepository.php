<?php // src/Repository/EspecialidadProtagonistaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EspecialidadProtagonista;
use PDO;

final class EspecialidadProtagonistaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idProtagonista, int $idEspecialidad): ?EspecialidadProtagonista
    {
        return $this->buscarUno(
            'SELECT * FROM especialidad_protagonista WHERE id_protagonista = ? AND id_especialidad = ?',
            [$idProtagonista, $idEspecialidad],
            EspecialidadProtagonista::class,
        );
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT ep.*
               FROM especialidad_protagonista ep
               JOIN especialidad e ON e.id = ep.id_especialidad
              WHERE ep.id_protagonista = ?
              ORDER BY e.id_area, e.numero',
            [$idProtagonista],
            EspecialidadProtagonista::class,
        );
    }

    public function insertar(EspecialidadProtagonista $ep): void
    {
        $this->ejecutar(
            'INSERT INTO especialidad_protagonista (id_protagonista, id_especialidad, estado, fecha)
             VALUES (:id_protagonista, :id_especialidad, :estado, :fecha)',
            $this->parametros($ep),
        );
    }

    public function actualizar(EspecialidadProtagonista $ep): void
    {
        $this->ejecutar(
            'UPDATE especialidad_protagonista
                SET estado = :estado, fecha = :fecha
              WHERE id_protagonista = :id_protagonista AND id_especialidad = :id_especialidad',
            $this->parametros($ep),
        );
    }

    public function eliminar(int $idProtagonista, int $idEspecialidad): void
    {
        $this->ejecutar(
            'DELETE FROM especialidad_protagonista WHERE id_protagonista = ? AND id_especialidad = ?',
            [$idProtagonista, $idEspecialidad],
        );
    }

    private function parametros(EspecialidadProtagonista $ep): array
    {
        return [
            'id_protagonista' => $ep->idProtagonista,
            'id_especialidad' => $ep->idEspecialidad,
            'estado'          => $ep->estado->value,
            'fecha'           => $ep->fecha,
        ];
    }
}