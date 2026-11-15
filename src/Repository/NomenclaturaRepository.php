<?php // src/Repository/NomenclaturaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Nomenclatura;
use PDO;

final class NomenclaturaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(string $clave, int $idRama): ?Nomenclatura
    {
        return $this->buscarUno(
            'SELECT * FROM nomenclatura WHERE clave = ? AND id_rama = ?',
            [$clave, $idRama],
            Nomenclatura::class,
        );
    }

    public function listarPorRama(int $idRama): array
    {
        $lista = $this->listar(
            'SELECT * FROM nomenclatura WHERE id_rama = ? ORDER BY clave',
            [$idRama],
            Nomenclatura::class,
        );
        return array_column($lista, null, 'clave');
    }
}