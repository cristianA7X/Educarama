<?php
// src/Service/Vista/ResumenProgresion.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

use Educarama\Model\Etapa;
use Educarama\Model\Insignia;
use Educarama\Model\Rama;

/**
 * Situación de la progresión de un protagonista en la rama de su sección.
 * La arma ProgresionService::resumen(); la usan el perfil del protagonista y
 * los contadores de la pantalla de competencias. Todo se calcula en vivo.
 */
final class ResumenProgresion
{
    /**
     * @param ContadorArea[] $areas   Una por área de la rama, en orden
     * @param Aviso[]        $adeudado Lo que falta para confirmar la etapa en curso (vacío = está lista)
     */
    public function __construct(
        public Rama      $rama,
        public ?Etapa    $etapaActual,           // Última lograda; null si no logró ninguna
        public ?Etapa    $etapaEnCurso,          // La siguiente; null si logró todas
        public int       $enCursoElegidas,       // En proceso + desarrolladas de la etapa en curso
        public int       $enCursoDesarrolladas,  // Contador "Etapa en curso: X/cupo"
        public int       $desarrolladasTotal,    // Contador "Total: X/total de la rama"
        public int       $competenciasTotal,
        public array     $areas,
        public array     $adeudado,
        public ?Insignia $insigniaPendiente,     // Insignia de la etapa en curso que todavía no recibió
    ) {}

    /** Cupo de la etapa en curso (0 si ya logró todas). */
    public function cupoEnCurso(): int
    {
        return $this->etapaEnCurso?->competenciasEtapa ?? 0;
    }

    /** True si hay etapa en curso y no le falta nada para confirmarla. */
    public function etapaLista(): bool
    {
        return $this->etapaEnCurso !== null && $this->adeudado === [];
    }
}
