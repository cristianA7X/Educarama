<?php // src/Repository/RolRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Rol;
use PDO;

final class RolRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function listarTodos(): array
    {
        return $this->listar('SELECT * FROM rol ORDER BY nivel', [], Rol::class);
    }

    public function buscarPorId(int $id): ?Rol
    {
        return $this->buscarUno('SELECT * FROM rol WHERE id = ?', [$id], Rol::class);
    }

    public function buscarPorNivel(NivelRol $nivel): ?Rol
    {
        return $this->buscarUno('SELECT * FROM rol WHERE nivel = ?', [$nivel->value], Rol::class);
    }
}