<?php
// src/Repository/EventoSeccionRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\EventoSeccion;

/**
 * Lectura y escritura de `evento_seccion`: a qué secciones pertenece un evento.
 * No tiene id propio: cada fila se identifica por id_evento + id_seccion.
 *
 * ⚠️ Un evento sin filas acá es de TODO el grupo. Por eso, para saber los eventos
 * que ve una sección, se usa EventoRepository::listarPorSeccion(), no listarPorSeccion() de acá.
 *
 * Que la sección sea del mismo grupo que el evento lo valida el Service.
 */
final class EventoSeccionRepository extends RepositorioBase
{
    /** @return EventoSeccion[] Las secciones de un evento; vacío = evento de todo el grupo. */
    public function listarPorEvento(int $idEvento): array
    {
        return $this->listar(
            'SELECT * FROM evento_seccion WHERE id_evento = ? ORDER BY id_seccion',
            [$idEvento],
            EventoSeccion::class,
        );
    }

    /** @return EventoSeccion[] Solo los eventos asignados explícitamente a la sección (sin los de todo el grupo). */
    public function listarPorSeccion(int $idSeccion): array
    {
        return $this->listar(
            'SELECT * FROM evento_seccion WHERE id_seccion = ? ORDER BY id_evento',
            [$idSeccion],
            EventoSeccion::class,
        );
    }

    /**
     * Las filas de todos los eventos de un grupo, en una sola consulta (para
     * los listados: evita una consulta por evento).
     *
     * @return EventoSeccion[]
     */
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

    /** Asigna el evento a una sección. Si ya estaba asignado, la base lanza una excepción (PK duplicada). */
    public function insertar(EventoSeccion $eventoSeccion): void
    {
        $this->ejecutar(
            'INSERT INTO evento_seccion (id_evento, id_seccion) VALUES (:id_evento, :id_seccion)',
            ['id_evento' => $eventoSeccion->idEvento, 'id_seccion' => $eventoSeccion->idSeccion],
        );
    }

    /**
     * Quita la sección del evento.
     * ⚠️ Si era la última, el evento pasa a ser de todo el grupo.
     */
    public function eliminar(int $idEvento, int $idSeccion): void
    {
        $this->ejecutar(
            'DELETE FROM evento_seccion WHERE id_evento = ? AND id_seccion = ?',
            [$idEvento, $idSeccion],
        );
    }

    /**
     * Quita todas las secciones del evento: pasa a ser de todo el grupo.
     * También sirve para reemplazar la lista: el Service borra todo e inserta
     * las nuevas dentro de una transacción.
     */
    public function eliminarPorEvento(int $idEvento): void
    {
        $this->ejecutar('DELETE FROM evento_seccion WHERE id_evento = ?', [$idEvento]);
    }
}
