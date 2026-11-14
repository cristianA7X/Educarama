<?php
// config/config.php
//
// Configuración general del proyecto.
//
// Los valores de abajo son los de un XAMPP recién instalado. Si en tu máquina
// algo cambia (por ejemplo, tu MySQL tiene contraseña), NO edites este archivo:
// creá config/config.local.php y definí ahí solo lo que cambia. Ese archivo
// está en .gitignore, así que no pisa la configuración de tus compañeros.
//
// Ejemplo de config/config.local.php:
//   <?php
//   define('DB_PASS', 'mi_contraseña');

declare(strict_types=1);

$configLocal = __DIR__ . '/config.local.php';
if (is_file($configLocal)) {
    require $configLocal;
}

// URL base del proyecto: la parte de la dirección que va después del dominio.
// Con XAMPP y la carpeta en htdocs/Educarama, la página de inicio es
// http://localhost/Educarama/public/index.php, entonces BASE_URL es '/Educarama'.
// Todos los links se arman con la función url() de config/bootstrap.php,
// así que si la carpeta cambia de nombre o se sube a un hosting,
// se cambia solo esta línea.
defined('BASE_URL') || define('BASE_URL', '/Educarama');

// Zona horaria de las fechas que calcula PHP (por ejemplo, "hoy" en los
// próximos eventos). XAMPP trae Europe/Berlin por defecto, que en Argentina
// cambia de día unas horas antes.
defined('ZONA_HORARIA') || define('ZONA_HORARIA', 'America/Argentina/Buenos_Aires');
date_default_timezone_set(ZONA_HORARIA);

// Conexión a la base de datos (la usa Educarama\Config\Database).
defined('DB_HOST')    || define('DB_HOST', 'localhost');
defined('DB_NAME')    || define('DB_NAME', 'educarama_v2');
defined('DB_USER')    || define('DB_USER', 'root');
defined('DB_PASS')    || define('DB_PASS', '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

// Almacén de archivos subidos (lo usa Educarama\Almacen\AlmacenArchivos).
// ALMACEN_RUTA es la carpeta donde se guardan. Por defecto, storage/ en la raíz
// del proyecto, protegida con un .htaccess (solo sirve en Apache). Para sacarla
// de htdocs, definí en config.local.php una ruta absoluta, por ejemplo:
//   define('ALMACEN_RUTA', 'C:/educarama-archivos');
defined('ALMACEN_RUTA')             || define('ALMACEN_RUTA', dirname(__DIR__) . '/storage');
defined('ALMACEN_TAMANIO_MAXIMO')   || define('ALMACEN_TAMANIO_MAXIMO', 5 * 1024 * 1024);  // 5 MB, en bytes
// Tipo MIME real (el que detecta finfo, no el que manda el navegador) => extensión con la que se guarda.
defined('ALMACEN_TIPOS_PERMITIDOS') || define('ALMACEN_TIPOS_PERMITIDOS', [
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
]);
