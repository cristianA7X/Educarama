<?php // src/Repository/EventoSeccionRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EventoSeccion;
use PDO;

final class EventoSeccionRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function listarPorEvento(int $idEvento): array
    {
        return $this->listar(
            'SELECT * FROM evento_seccion WHERE id_evento = ? ORDER BY id_seccion',
            [$idEvento],
            EventoSeccion::class,
        );
    }

    public function listarPorSeccion(int $idSeccion): array
    {
        return $this->listar(
            'SELECT * FROM evento_seccion WHERE id_seccion = ? ORDER BY id_evento',
            [$idSeccion],
            EventoSeccion::class,
        );
    }

    public function listarPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT es.*
               FROM evento_seccion es
               JOIN evento e ON e.id = es.id_evento
              WHERE e.id_grupo = ?
              ORDER BY es.id_evento, es.id_seccion',
            [$idGrupo],
            EventoSeccion::class,
        );
    }

    public function insertar(EventoSeccion $eventoSeccion): void
    {
        $this->ejecutar(
            'INSERT INTO evento_seccion (id_evento, id_seccion) VALUES (:id_evento, :id_seccion)',
            ['id_evento' => $eventoSeccion->idEvento, 'id_seccion' => $eventoSeccion->idSeccion],
        );
    }

    public function eliminar(int $idEvento, int $idSeccion): void
    {
        $this->ejecutar(
            'DELETE FROM evento_seccion WHERE id_evento = ? AND id_seccion = ?',
            [$idEvento, $idSeccion],
        );
    }

    public function eliminarPorEvento(int $idEvento): void
    {
        $this->ejecutar('DELETE FROM evento_seccion WHERE id_evento = ?', [$idEvento]);
    }
}