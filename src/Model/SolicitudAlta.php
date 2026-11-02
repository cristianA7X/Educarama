<?php
// src/Model/SolicitudAlta.php

declare(strict_types=1);

namespace Educarama\Model;

use Educarama\Model\Enum\EstadoSolicitud;

/**
 * Pedido de alta de un grupo y sus secciones, que aprueba un dev.
 *
 * Tabla `solicitud_alta`. Tabla dinámica: la cargan los usuarios.
 * datos_grupo y datos_secciones se guardan como texto JSON en la base;
 * acá ya llegan convertidos a arrays de PHP.
 */
final class SolicitudAlta
{
    public function __construct(
        public ?int            $id,              // null mientras no se guardó en la base
        public int             $idUsuario,       // Quién la envió
        public array           $datosGrupo,      // Lo que cargó el solicitante sobre el grupo
        public array           $datosSecciones,  // Lista de secciones a crear
        public EstadoSolicitud $estado,
        public ?string         $fecha,           // La pone la base al insertar
        public ?int            $idAdminRevisor,  // Dev que la revisó
    ) {}

    /**
     * Arma el objeto desde una fila de `solicitud_alta`, tal como la devuelve PDO
     * (array asociativo con los nombres de columna de la base).
     */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id: (int) $fila['id'],
            idUsuario: (int) $fila['id_usuario'],
            datosGrupo: json_decode((string) $fila['datos_grupo'], true, 512, JSON_THROW_ON_ERROR),
            datosSecciones: json_decode((string) $fila['datos_secciones'], true, 512, JSON_THROW_ON_ERROR),
            estado: EstadoSolicitud::from($fila['estado']),
            fecha: $fila['fecha'] === null ? null : (string) $fila['fecha'],
            idAdminRevisor: $fila['id_admin_revisor'] === null ? null : (int) $fila['id_admin_revisor'],
        );
    }
}
