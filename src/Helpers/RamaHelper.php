<?php
// src/Helpers/RamaHelper.php
// Traduce el id de categoría de rama (categoria_rama.id) a la clase CSS de color.

if (!function_exists('claseRama')) {
    function claseRama($idCategoria): string
    {
        return match ((int) $idCategoria) {
            1 => 'rama-manada',     // Amarillo
            2 => 'rama-unidad',     // Verde
            3 => 'rama-caminantes', // Celeste
            4 => 'rama-rover',      // Rojo
            default => '',          // Sin rama: usa el color por defecto (:root)
        };
    }
}
