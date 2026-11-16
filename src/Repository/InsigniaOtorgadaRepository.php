<?php // src/Repository/InsigniaOtorgadaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\InsigniaOtorgada;
use PDO;

final class InsigniaOtorgadaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idProtagonista, int $idInsignia): ?InsigniaOtorgada
    {
        return $this->buscarUno(
            'SELECT * FROM insignia_otorgada WHERE id_protagonista = ? AND id_insignia = ?',
            [$idProtagonista, $idInsignia],
            InsigniaOtorgada::class,
        );
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT * FROM insignia_otorgada WHERE id_protagonista = ? ORDER BY fecha, id_insignia',
            [$idProtagonista],
            InsigniaOtorgada::class,
        );
    }

    public function insertar(InsigniaOtorgada $insigniaOtorgada): void
    {
        $this->ejecutar(
            'INSERT INTO insignia_otorgada (id_protagonista, id_insignia, fecha) VALUES (?, ?, ?)',
            [$insigniaOtorgada->idProtagonista, $insigniaOtorgada->idInsignia, $insigniaOtorgada->fecha],
        );
    }

    public function eliminar(int $idProtagonista, int $idInsignia): void
    {
        $this->ejecutar(
            'DELETE FROM insignia_otorgada WHERE id_protagonista = ? AND id_insignia = ?',
            [$idProtagonista, $idInsignia],
        );
    }
}