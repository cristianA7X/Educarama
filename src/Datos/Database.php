<?php
// src/Datos/Database.php
// Esta clase es responsable de manejar la conexión 
// a la base de datos utilizando el patrón Singleton 
// para asegurar que solo exista una instancia de la 
// conexión durante toda la ejecución del script.

class Database {
    // Variable estática para guardar la única instancia de la conexión (Patrón Singleton)
    private static ?PDO $conexion = null;

    // Método estático para obtener la conexión
    public static function getConexion(): PDO {
        if (self::$conexion === null) {
            
            // --- CONFIGURACIÓN DE TU BASE DE DATOS ---
            $host = 'localhost';
            $db   = 'educarama'; 
            $user = 'root';       
            $pass = '';           
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            
            try {
                // Creamos la conexión PDO
                self::$conexion = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
                    PDO::ATTR_EMULATE_PREPARES   => false,                  
                ]);
            } catch (Throwable $e) {
                // Si falla la conexión, frenamos la ejecución y mostramos el error
                http_response_code(500);
                die(json_encode([
                    "status" => "error", 
                    "message" => "Error fatal de conexión a la Base de Datos: " . $e->getMessage()
                ]));
            }
        }
        
        return self::$conexion;
    }
}

