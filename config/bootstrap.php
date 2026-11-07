<?php
// config/bootstrap.php
//
// Punto de arranque: cada página o controlador lo incluye UNA vez, al principio:
//
//   require_once __DIR__ . '/../../config/bootstrap.php';   // ajustar los ../ según la carpeta
//
// y a partir de ahí todas las clases del proyecto se cargan solas.
// No hace falta instalar nada: el autoload usa una función que trae PHP.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// Autoload
//
// spl_autoload_register() le pasa a PHP una función que se ejecuta cada vez que
// el código usa una clase que todavía no se cargó. La función recibe el nombre
// completo de la clase (con su namespace) y hace el require del archivo.
//
// Regla para encontrar el archivo (es la convención PSR-4, la misma que usa
// Composer, así que si algún día se suma Composer no hay que reescribir nada):
//
//   Educarama\Config\Database        →  config/Database.php
//   Educarama\Model\Protagonista     →  src/Model/Protagonista.php
//   Educarama\Model\Enum\NivelRol    →  src/Model/Enum/NivelRol.php
//
// Por eso el namespace de cada clase tiene que coincidir con su carpeta,
// y el nombre de la clase con el nombre del archivo (mayúsculas incluidas:
// en Windows da igual, pero en un hosting Linux no).
// ---------------------------------------------------------------------------
spl_autoload_register(function (string $clase): void {
    // Prefijo del namespace => carpeta donde están sus archivos.
    // El más específico va primero: si no, Educarama\Config\... buscaría en src/Config/.
    $prefijos = [
        'Educarama\\Config\\' => __DIR__ . '/',
        'Educarama\\'         => __DIR__ . '/../src/',
    ];

    foreach ($prefijos as $prefijo => $carpeta) {
        if (!str_starts_with($clase, $prefijo)) {
            continue;
        }

        // Lo que sigue al prefijo es la ruta dentro de la carpeta:
        // 'Model\Protagonista' → 'Model/Protagonista.php'
        $relativa = substr($clase, strlen($prefijo));
        $archivo = $carpeta . str_replace('\\', '/', $relativa) . '.php';

        if (is_file($archivo)) {
            require $archivo;
        }
        // Si el archivo no existe, PHP muestra "Class ... not found" con el nombre
        // completo: revisar namespace, carpeta y mayúsculas.
        return;
    }
});

/**
 * Arma una URL interna del proyecto a partir de BASE_URL.
 *
 *   url('views/pages/protagonistas.php')  →  '/Educarama/views/pages/protagonistas.php'
 *   url('public/css/styles.css')           →  '/Educarama/public/css/styles.css'
 *
 * Usarla siempre en vez de escribir '/Educarama/...' a mano.
 */
function url(string $ruta = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($ruta, '/');
}

/**
 * Escapa un texto para imprimirlo en HTML (htmlspecialchars con UTF-8 y comillas).
 *
 *   <p><?= e($protagonista->persona->nombre) ?></p>
 *
 * Todo dato que viene de la base o del usuario se imprime con e(): evita que
 * un nombre como "<script>" se ejecute en la página (XSS).
 */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
