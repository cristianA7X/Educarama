<?php
// src/Service/Excepcion/RequisitosPendientesException.php

declare(strict_types=1);

namespace Educarama\Service\Excepcion;

use Educarama\Service\Vista\Aviso;

/**
 * Faltan requisitos que se pueden saltear (cupo, acciones, tareas).
 * No se guardó nada. Uso en el Controller:
 *
 *   try {
 *       $servicio->confirmarEtapa($idUsuario, $idProtagonista, $idEtapa, $elemento, $confirmarIgual);
 *   } catch (RequisitosPendientesException $e) {
 *       // Mostrar $e->avisos y preguntar "¿Registrar igual?".
 *       // Si el educador acepta, se repite la llamada con $confirmarIgual = true.
 *   }
 *
 * Así nadie puede registrar algo con requisitos pendientes sin haber visto el aviso.
 */
final class RequisitosPendientesException extends ServicioException
{
    /** @param Aviso[] $avisos */
    public function __construct(public readonly array $avisos)
    {
        parent::__construct(
            'Hay requisitos sin cumplir: ' . implode(' ', array_map(fn (Aviso $a) => $a->mensaje, $avisos))
        );
    }
}
