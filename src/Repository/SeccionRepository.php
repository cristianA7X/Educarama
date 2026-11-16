<?php // src/Repository/SeccionRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Seccion;
use PDO;

final class SeccionRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Seccion
    {
        return $this->buscarUno('SELECT * FROM seccion WHERE id = ?', [$id], Seccion::class);
    }

    public function listarPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT * FROM seccion WHERE id_grupo = ? ORDER BY id_rama, nombre',
            [$idGrupo],
            Seccion::class,
        );
    }

    public function insertar(Seccion $seccion): int
    {
        $seccion->id = $this->insertarFila(
            'INSERT INTO seccion (nombre, id_grupo, id_rama) VALUES (:nombre, :id_grupo, :id_rama)',
            ['nombre' => $seccion->nombre, 'id_grupo' => $seccion->idGrupo, 'id_rama' => $seccion->idRama],
        );
        return $seccion->id;
    }

    public function actualizar(Seccion $seccion): void
    {
        $this->ejecutar(
            'UPDATE seccion SET nombre = :nombre, id_grupo = :id_grupo, id_rama = :id_rama WHERE id = :id',
            [
                'nombre'   => $seccion->nombre,
                'id_grupo' => $seccion->idGrupo,
                'id_rama'  => $seccion->idRama,
                'id'       => $this->exigirId($seccion->id),
            ],
        );
    }
}