<?php // src/Repository/InsigniaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Insignia;
use PDO;

final class InsigniaRepository extends RepositorioEstatico
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Insignia
    {
        return $this->buscarUno('SELECT * FROM insignia WHERE id = ?', [$id], Insignia::class);
    }

    public function listarPorRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM insignia WHERE id_rama = ? ORDER BY id',
            [$idRama],
            Insignia::class,
        );
    }

    public function listarParaRama(int $idRama): array
    {
        return $this->listar(
            'SELECT * FROM insignia
              WHERE id_rama = ? OR id_rama IS NULL
              ORDER BY id_etapa IS NULL, id_etapa, id',
            [$idRama],
            Insignia::class,
        );
    }

    public function buscarPorEtapa(int $idEtapa): ?Insignia
    {
        return $this->buscarUno(
            'SELECT * FROM insignia WHERE id_etapa = ? ORDER BY id LIMIT 1',
            [$idEtapa],
            Insignia::class,
        );
    }
}