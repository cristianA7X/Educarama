<?php
// src/Service/Vista/TipoAviso.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/**
 * Qué tipo de aviso es. La vista lo usa para decidir cómo mostrarlo
 * (json_encode lo manda como su texto, ej. "cupo_excedido").
 *
 * Hay dos familias:
 *  - Requisitos que se pueden saltear: viajan en una RequisitosPendientesException
 *    y piden confirmación antes de guardar.
 *  - Sugerencias: viajan en un ResultadoProgresion, después de guardar, y no frenan nada.
 */
enum TipoAviso: string
{
    // --- Requisitos que se pueden saltear ---
    case CupoExcedido       = 'cupo_excedido';         // Al elegir: la etapa en curso ya llegó a su cupo
    case SinEtapaEnCurso    = 'sin_etapa_en_curso';    // Al elegir: ya logró todas las etapas
    case AccionesPendientes = 'acciones_pendientes';   // Al desarrollar: faltan acciones requeridas
    case CupoIncompleto     = 'cupo_incompleto';       // Al confirmar etapa: faltan competencias desarrolladas
    case TareaPendiente     = 'tarea_pendiente';       // Al confirmar etapa: falta una tarea

    // --- Sugerencias ---
    case CompetenciaLista   = 'competencia_lista';     // Cumplió la última acción requerida
    case EtapaLista         = 'etapa_lista';           // Las desarrolladas de la etapa en curso llegaron al cupo
}
