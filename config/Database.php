<?php
// config/Database.php

declare(strict_types=1);

namespace Educarama\Config;

use PDO;

/**
 * Conexión única a la base de datos (patrón Singleton).
 *
 * Cualquier repositorio pide la conexión con Database::getConexion(). La primera
 * vez se crea; las siguientes se devuelve la misma, así una página nunca abre
 * dos conexiones.
 *
 * Los datos de conexión salen de config/config.php (constantes DB_*).
 */
final class Database
{
    private static ?PDO $conexion = null;

    // Nadie puede hacer "new Database()": se usa solo getConexion().
    private function __construct() {}

    public static function getConexion(): PDO
    {
        if (self::$conexion === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

            self::$conexion = new PDO($dsn, DB_USER, DB_PASS, [
                // Ante cualquier error de SQL, PDO lanza una excepción (en vez de devolver false en silencio).
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // fetch() devuelve arrays asociativos: ['id' => 1, 'nombre' => ...].
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Consultas preparadas reales del servidor: los números llegan como int y no como string.
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$conexion;
    }
}
