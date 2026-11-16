<?php // src/Repository/EventoRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Evento;
use PDO;

final class EventoRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Evento
    {
        return $this->buscarUno('SELECT * FROM evento WHERE id = ?', [$id], Evento::class);
    }

    public function listarPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT * FROM evento WHERE id_grupo = ? ORDER BY fecha_inicio, id',
            [$idGrupo],
            Evento::class,
        );
    }

    public function listarPorSeccion(int $idSeccion): array
    {
        return $this->listar(
            'SELECT e.*
               FROM evento e
               JOIN seccion s ON s.id_grupo = e.id_grupo
              WHERE s.id = :id_seccion
                AND (
                      NOT EXISTS (SELECT 1 FROM evento_seccion es WHERE es.id_evento = e.id)
                   OR EXISTS (SELECT 1 FROM evento_seccion es
                               WHERE es.id_evento = e.id AND es.id_seccion = :id_seccion2)
                )
              ORDER BY e.fecha_inicio, e.id',
            ['id_seccion' => $idSeccion, 'id_seccion2' => $idSeccion],
            Evento::class,
        );
    }

    public function insertar(Evento $evento): int
    {
        $evento->id = $this->insertarFila(
            'INSERT INTO evento (id_grupo, titulo, descripcion, fecha_inicio, fecha_fin,
                                 prioridad, activo, es_publico, id_tipo_evento)
             VALUES (:id_grupo, :titulo, :descripcion, :fecha_inicio, :fecha_fin,
                     :prioridad, :activo, :es_publico, :id_tipo_evento)',
            $this->parametros($evento),
        );
        return $evento->id;
    }

    public function actualizar(Evento $evento): void
    {
        $parametros = $this->parametros($evento);
        unset($parametros['id_grupo']);
        $parametros['id'] = $this->exigirId($evento->id);
        $this->ejecutar(
            'UPDATE evento
                SET titulo = :titulo, descripcion = :descripcion,
                    fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin,
                    prioridad = :prioridad, activo = :activo, es_publico = :es_publico,
                    id_tipo_evento = :id_tipo_evento
              WHERE id = :id',
            $parametros,
        );
    }

    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM evento WHERE id = ?', [$id]);
    }

    private function parametros(Evento $evento): array
    {
        return [
            'id_grupo'       => $evento->idGrupo,
            'titulo'         => $evento->titulo,
            'descripcion'    => $evento->descripcion,
            'fecha_inicio'   => $evento->fechaInicio,
            'fecha_fin'      => $evento->fechaFin,
            'prioridad'      => $evento->prioridad->value,
            'activo'         => (int) $evento->activo,
            'es_publico'     => (int) $evento->esPublico,
            'id_tipo_evento' => $evento->idTipoEvento,
        ];
    }
}