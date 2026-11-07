<?php
// src/Helper/Vista.php

declare(strict_types=1);

namespace Educarama\Helper;

use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Model\Enum\Prioridad;

/**
 * Pequeñas ayudas para las páginas de views/pages/. Sin reglas de negocio:
 * solo traducen datos a lo que se imprime.
 */
final class Vista
{
    private function __construct() {}

    /**
     * Clase CSS de la rama, que define sus colores (--rama-primario y
     * --rama-secundario en public/css/styles.css). Sale del id de la rama, nunca
     * del nombre de la sección (bug del legacy: deducirla con strpos).
     * Sin rama (o un id desconocido), '' = la paleta violeta de siempre.
     */
    public static function claseRama(?int $idRama): string
    {
        return match ($idRama) {
            1       => 'rama-manada',
            2       => 'rama-unidad',
            3       => 'rama-caminantes',
            4       => 'rama-rover',
            default => '',
        };
    }

    /** Ícono de Bootstrap Icons de cada rama, para las tarjetas de sección. */
    public static function iconoRama(?int $idRama): string
    {
        return match ($idRama) {
            1       => 'bi-stars',
            2       => 'bi-compass',
            3       => 'bi-signpost-split',
            4       => 'bi-globe-americas',
            default => 'bi-flag',
        };
    }

    /** Color (de Bootstrap) de la etiqueta de la prioridad de un evento: alta roja, media amarilla, baja gris. */
    public static function colorPrioridad(Prioridad $prioridad): string
    {
        return match ($prioridad) {
            Prioridad::Alta  => 'danger',
            Prioridad::Media => 'warning',
            Prioridad::Baja  => 'secondary',
        };
    }

    /** Color de la etiqueta del estado de una solicitud de alta. */
    public static function colorEstado(EstadoSolicitud $estado): string
    {
        return match ($estado) {
            EstadoSolicitud::Pendiente => 'warning',
            EstadoSolicitud::Aprobada  => 'success',
            EstadoSolicitud::Rechazada => 'danger',
        };
    }

    /** '14/11/2026', o '10/01/2027 al 17/01/2027' si el evento tiene fecha de fin. */
    public static function periodo(string $inicio, ?string $fin): string
    {
        return $fin === null || $fin === $inicio
            ? self::fecha($inicio)
            : self::fecha($inicio) . ' al ' . self::fecha($fin);
    }

    /** '2026-10-28 20:15:00' → '28/10/2026' (la fecha de un DATETIME). */
    public static function fechaDeFechaHora(?string $fechaHora): string
    {
        return self::fecha($fechaHora === null ? null : substr($fechaHora, 0, 10));
    }

    /** '2026-11-14' → '14/11/2026'. Si no es una fecha 'AAAA-MM-DD', la devuelve igual. */
    public static function fecha(?string $fecha): string
    {
        if ($fecha === null || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m) !== 1) {
            return $fecha ?? '';
        }

        return "$m[3]/$m[2]/$m[1]";
    }
}
