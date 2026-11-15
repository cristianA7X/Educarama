<?php
// src/Repository/EventoRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Evento;

/**
 * Lectura y escritura de `evento`: las actividades de un grupo.
 *
 * Un evento es de todo el grupo o de algunas secciones. Las secciones están en
 * `evento_seccion` (ver EventoSeccionRepository): sin filas ahí = todo el grupo.
 *
 * Los listados traen activos e inactivos; filtrar por `activo` o `es_publico`
 * (lo que ve un Supervisor) lo decide el Service.
 */
final class EventoRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Evento
    {
        return $this->buscarUno('SELECT * FROM evento WHERE id = ?', [$id], Evento::class);
    }

    /** @return Evento[] Todos los eventos del grupo, de cualquier sección, ordenados por fecha de inicio. */
    public function listarPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT * FROM evento WHERE id_grupo = ? ORDER BY fecha_inicio, id',
            [$idGrupo],
            Evento::class,
        );
    }

    /**
     * Los eventos que le tocan a una sección, ordenados por fecha de inicio:
     *  - los de todo su grupo (sin filas en evento_seccion), y
     *  - los asignados a esa sección.
     *
     * El grupo sale de la propia sección (JOIN), así no hace falta pasarlo
     * y no se cuelan eventos de otro grupo.
     *
     * @return Evento[]
     */
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
            // Sin emulación de prepares, un mismo :nombre no se puede usar dos veces.
            ['id_seccion' => $idSeccion, 'id_seccion2' => $idSeccion],
            Evento::class,
        );
    }

    /** Guarda un evento nuevo, le completa el id y lo devuelve. Las secciones se cargan aparte. */
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

    /** Actualiza todos los datos del evento. El grupo no cambia: un evento no se pasa a otro grupo. */
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

    /** Borra el evento. La base borra en cascada sus filas de evento_seccion. */
    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM evento WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed> */
    private function parametros(Evento $evento): array
    {
        return [
            'id_grupo'       => $evento->idGrupo,
            'titulo'         => $evento->titulo,
            'descripcion'    => $evento->descripcion,
            'fecha_inicio'   => $evento->fechaInicio,
            'fecha_fin'      => $evento->fechaFin,
            'prioridad'      => $evento->prioridad->value,   // enum → texto de la base
            'activo'         => (int) $evento->activo,       // bool → TINYINT
            'es_publico'     => (int) $evento->esPublico,
            'id_tipo_evento' => $evento->idTipoEvento,
        ];
    }
}
