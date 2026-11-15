<?php
// src/Model/Evento.php

declare(strict_types=1);

namespace Educarama\Model;

use Educarama\Model\Enum\Prioridad;

/**
 * Evento de un grupo; puede ser de algunas secciones o de todo el grupo.
 *
 * Tabla `evento`. Tabla dinámica: la cargan los usuarios.
 * Las secciones del evento están en EventoSeccion. Sin filas ahí = evento de todo el grupo.
 */
final class Evento
{
    public function __construct(
        public ?int      $id,            // null mientras no se guardó en la base
        public int       $idGrupo,
        public string    $titulo,
        public string    $descripcion,
        public string    $fechaInicio,   // Formato 'AAAA-MM-DD'
        public ?string   $fechaFin,
        public Prioridad $prioridad,
        public bool      $activo,
        public bool      $esPublico,     // Si lo ve un Supervisor
        public ?int      $idTipoEvento,  // Reservado para plantillas de evento (futuro)
    ) {}

    /**
     * Arma el objeto desde una fila de `evento`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idGrupo: (int) $fila['id_grupo'],
            titulo: (string) $fila['titulo'],
            descripcion: (string) $fila['descripcion'],
            fechaInicio: (string) $fila['fecha_inicio'],
            fechaFin: $fila['fecha_fin'] === null ? null : (string) $fila['fecha_fin'],
            prioridad: Prioridad::from($fila['prioridad']),
            activo: (bool) $fila['activo'],
            esPublico: (bool) $fila['es_publico'],
            idTipoEvento: $fila['id_tipo_evento'] === null ? null : (int) $fila['id_tipo_evento'],
        );
    }
}
