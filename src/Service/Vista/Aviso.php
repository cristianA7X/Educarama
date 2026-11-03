<?php
// src/Service/Vista/Aviso.php

declare(strict_types=1);

namespace Educarama\Service\Vista;

/**
 * Un aviso o una sugerencia para mostrar al educador.
 * El mensaje ya viene listo para la pantalla; el tipo sirve para elegir el estilo o la acción.
 */
final class Aviso
{
    public function __construct(
        public TipoAviso $tipo,
        public string    $mensaje,
    ) {}
}
