<?php // src/Repository/EspecialidadRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Especialidad;
use PDO;

final class EspecialidadRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Especialidad
    {
        return $this->buscarUno('SELECT * FROM especialidad WHERE id = ?', [$id], Especialidad::class);
    }

    public function listarTodas(): array
    {
        return $this->listar('SELECT * FROM especialidad ORDER BY id_area, numero', [], Especialidad::class);
    }

    public function listarPorArea(int $idArea): array
    {
        return $this->listar(
            'SELECT * FROM especialidad WHERE id_area = ? ORDER BY numero',
            [$idArea],
            Especialidad::class,
        );
    }
}