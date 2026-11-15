<?php
// src/Almacen/AlmacenArchivos.php

declare(strict_types=1);

namespace Educarama\Almacen;

/**
 * Guarda, envía y borra archivos subidos por los usuarios, en una carpeta que
 * no se puede abrir por URL.
 *
 * Es un componente genérico: no depende de ninguna otra clase del proyecto
 * (ni Models, ni Repositories, ni Services, ni la base de datos). No sabe qué
 * guarda: recibe un elemento de $_FILES y una subcarpeta, y devuelve dónde
 * quedó. Qué se anota en la base y quién puede ver cada archivo lo decide el
 * código que lo usa (en Educarama, DocumentoService).
 *
 * Uso típico:
 *
 *   $almacen = new AlmacenArchivos();
 *   $almacen->verificarLimiteDelServidor();                  // antes de leer $_FILES
 *   $guardado = $almacen->guardar($_FILES['archivo'], 'documentos/7');
 *   // ... anotar $guardado->rutaRelativa en la base ...
 *   $almacen->enviar($rutaRelativa, $nombreOriginal);         // descarga (después de chequear permisos)
 *   $almacen->borrar($rutaRelativa);
 *
 * Seguridad:
 *  - El tipo se detecta por el contenido del archivo (finfo), nunca por la
 *    extensión ni por el tipo que manda el navegador. Se guarda con la
 *    extensión que corresponde a ese tipo real.
 *  - El nombre en el disco es aleatorio (32 caracteres hexadecimales): no se
 *    puede adivinar y no usa nada de lo que escribió el usuario.
 *  - Toda ruta recibida se valida para que no salga de la carpeta del almacén
 *    (nada de '../', rutas absolutas ni caracteres raros).
 *  - enviar() solo muestra en línea las imágenes; cualquier otro tipo (ej. PDF)
 *    se descarga como adjunto, aunque se pida en línea.
 *  - Los mensajes de ArchivoInvalidoException son para el usuario y nunca
 *    incluyen rutas. Los errores del sistema son \RuntimeException.
 *
 * ---------------------------------------------------------------------------
 * Cómo reutilizarlo en otro proyecto
 * ---------------------------------------------------------------------------
 *  1. Copiar la carpeta src/Almacen/ completa (AlmacenArchivos,
 *     ArchivoGuardado y ArchivoInvalidoException).
 *  2. Cambiar el namespace Educarama\Almacen por el del otro proyecto en los
 *     tres archivos, o dejarlo y registrarlo en su autoload.
 *  3. Definir las constantes (o pasar los valores al constructor):
 *       ALMACEN_RUTA              Carpeta donde se guardan (obligatoria).
 *       ALMACEN_TAMANIO_MAXIMO    Bytes. Si no se define: 5 MB.
 *       ALMACEN_TIPOS_PERMITIDOS  ['tipo/mime' => 'extension']. Si no se define: PDF, JPG y PNG.
 *  4. Proteger la carpeta:
 *       - En Apache, un .htaccess con "Require all denied" dentro de ella.
 *       - En cualquier otro servidor (Nginx, php -S...) el .htaccess no hace
 *         nada: poner ALMACEN_RUTA FUERA de la carpeta pública del sitio.
 *  5. Requiere PHP 8.1+ con la extensión fileinfo (viene activada en XAMPP).
 */
final class AlmacenArchivos
{
    private const TAMANIO_POR_DEFECTO = 5 * 1024 * 1024;

    private const TIPOS_POR_DEFECTO = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];

    /** Ruta absoluta y real de la carpeta del almacén, sin barra final. */
    private string $raiz;
    private int $tamanioMaximo;
    /** @var array<string, string> tipo MIME => extensión */
    private array $tiposPermitidos;

    /**
     * Cada parámetro que no se pase se toma de su constante (ALMACEN_*), y si
     * la constante tampoco existe, del valor por defecto (salvo la ruta, que es obligatoria).
     *
     * @param array<string, string>|null $tiposPermitidos tipo MIME => extensión
     */
    public function __construct(?string $ruta = null, ?int $tamanioMaximo = null, ?array $tiposPermitidos = null)
    {
        $ruta ??= defined('ALMACEN_RUTA')
            ? (string) constant('ALMACEN_RUTA')
            : throw new \LogicException('Falta definir ALMACEN_RUTA (la carpeta del almacén de archivos).');

        $this->tamanioMaximo = $tamanioMaximo
            ?? (defined('ALMACEN_TAMANIO_MAXIMO') ? (int) constant('ALMACEN_TAMANIO_MAXIMO') : self::TAMANIO_POR_DEFECTO);

        $this->tiposPermitidos = $tiposPermitidos
            ?? (defined('ALMACEN_TIPOS_PERMITIDOS') ? (array) constant('ALMACEN_TIPOS_PERMITIDOS') : self::TIPOS_POR_DEFECTO);

        if (!is_dir($ruta) && !mkdir($ruta, 0770, true) && !is_dir($ruta)) {
            throw new \RuntimeException('No se pudo crear la carpeta del almacén de archivos.');
        }

        $this->raiz = rtrim(str_replace('\\', '/', (string) realpath($ruta)), '/');
    }

    // =================================================================
    // Guardar
    // =================================================================

    /**
     * Valida el archivo subido y lo mueve a la subcarpeta, con un nombre aleatorio.
     *
     * @param array<string, mixed> $archivoSubido Un elemento de $_FILES, ej. $_FILES['archivo']
     * @param string $subcarpeta Relativa al almacén, ej. 'documentos/protagonista/7'. Se crea si no existe.
     *
     * @throws ArchivoInvalidoException si el archivo no llegó, está vacío, es muy grande o su tipo no se acepta
     * @throws \RuntimeException si falla algo del servidor (carpeta temporal, permisos de escritura)
     */
    public function guardar(array $archivoSubido, string $subcarpeta): ArchivoGuardado
    {
        $temporal = $this->validarSubida($archivoSubido);

        // Tamaño real del archivo recibido.
        $tamanio = (int) filesize($temporal);
        if ($tamanio === 0) {
            throw new ArchivoInvalidoException('El archivo está vacío.');
        }
        if ($tamanio > $this->tamanioMaximo) {
            throw new ArchivoInvalidoException($this->mensajeTamanio());
        }

        // Tipo real, leyendo el contenido. Lo que diga el navegador o la extensión no importa.
        $tipoMime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $extension = $this->tiposPermitidos[$tipoMime]
            ?? throw new ArchivoInvalidoException($this->mensajeTipos());

        // Carpeta de destino (validada) y nombre aleatorio.
        $subcarpeta = $this->normalizar($subcarpeta);
        $carpeta = $this->raiz . '/' . $subcarpeta;
        if (!is_dir($carpeta) && !mkdir($carpeta, 0770, true) && !is_dir($carpeta)) {
            throw new \RuntimeException('No se pudo crear la subcarpeta del almacén.');
        }

        $rutaRelativa = $subcarpeta . '/' . bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($temporal, $this->raiz . '/' . $rutaRelativa)) {
            throw new \RuntimeException('No se pudo mover el archivo subido al almacén.');
        }

        return new ArchivoGuardado(
            rutaRelativa: $rutaRelativa,
            nombreOriginal: $this->sanearNombre((string) $archivoSubido['name'], $extension),
            tipoMime: $tipoMime,
            tamanioBytes: $tamanio,
        );
    }

    /**
     * Si el pedido superó post_max_size, PHP descarta todo y deja $_POST y
     * $_FILES vacíos SIN avisar ningún error. Este método lo detecta y lanza el
     * mismo mensaje de "supera el tamaño máximo". Llamarlo al principio del
     * Controller que recibe la subida, antes de leer $_POST o $_FILES.
     *
     * @throws ArchivoInvalidoException
     */
    public function verificarLimiteDelServidor(): void
    {
        $largo = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $limite = self::bytesDeIni((string) ini_get('post_max_size'));

        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && $_POST === [] && $_FILES === []
            && $limite > 0 && $largo > $limite
        ) {
            throw new ArchivoInvalidoException($this->mensajeTamanio());
        }
    }

    // =================================================================
    // Enviar
    // =================================================================

    /**
     * Manda el archivo al navegador y termina el script. Usarlo solo después de
     * chequear que el usuario puede ver el archivo.
     *
     * @param string $nombreParaDescarga Nombre que verá el usuario (se sanea)
     * @param bool   $enLinea true = mostrarlo en el navegador. Solo vale para
     *                        imágenes; cualquier otro tipo se descarga igual.
     *
     * @throws ArchivoInvalidoException si el archivo no existe en el almacén
     */
    public function enviar(string $rutaRelativa, string $nombreParaDescarga, bool $enLinea = false): never
    {
        $ruta = $this->rutaExistente($rutaRelativa)
            ?? throw new ArchivoInvalidoException('El archivo no está disponible.');

        if (headers_sent()) {
            throw new \RuntimeException('No se puede enviar el archivo: ya se mandó contenido al navegador.');
        }

        // El tipo se vuelve a leer del contenido: no se confía en lo anotado en la base.
        $tipoMime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        $enLinea = $enLinea && str_starts_with($tipoMime, 'image/');

        // Si quedó algo en los buffers de salida (espacios, un BOM), arruinaría el archivo.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $nombre = $this->sanearNombre($nombreParaDescarga, null);
        // filename="..." en ASCII para navegadores viejos; filename* en UTF-8 (RFC 6266) para el resto.
        $nombreAscii = preg_replace('/[^\x20-\x7E]/', '_', $nombre) ?? 'archivo';

        header('Content-Type: ' . $tipoMime);
        header('Content-Length: ' . (string) filesize($ruta));
        header(sprintf(
            'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
            $enLinea ? 'inline' : 'attachment',
            $nombreAscii,
            rawurlencode($nombre),
        ));
        header('X-Content-Type-Options: nosniff');      // El navegador no "adivina" otro tipo
        header('Cache-Control: private, no-store');      // Son archivos privados: no se guardan en cachés

        readfile($ruta);
        exit;
    }

    // =================================================================
    // Borrar
    // =================================================================

    /** Borra un archivo. Si no existe, no hace nada. */
    public function borrar(string $rutaRelativa): void
    {
        $ruta = $this->rutaExistente($rutaRelativa);

        if ($ruta !== null && is_file($ruta) && !unlink($ruta)) {
            throw new \RuntimeException('No se pudo borrar el archivo del almacén.');
        }
    }

    /** Borra una subcarpeta con todo su contenido. Si no existe, no hace nada. */
    public function borrarCarpeta(string $subcarpeta): void
    {
        $carpeta = $this->rutaExistente($subcarpeta);

        if ($carpeta === null || !is_dir($carpeta)) {
            return;
        }

        // CHILD_FIRST: primero el contenido y después la carpeta que lo contiene.
        $elementos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($carpeta, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($elementos as $elemento) {
            /** @var \SplFileInfo $elemento */
            $ok = $elemento->isDir() && !$elemento->isLink()
                ? rmdir($elemento->getPathname())
                : unlink($elemento->getPathname());  // Un enlace se borra a sí mismo, sin seguirlo

            if (!$ok) {
                throw new \RuntimeException('No se pudo borrar el contenido de la carpeta del almacén.');
            }
        }

        if (!rmdir($carpeta)) {
            throw new \RuntimeException('No se pudo borrar la carpeta del almacén.');
        }
    }

    // =================================================================
    // Validaciones internas
    // =================================================================

    /**
     * Controla el elemento de $_FILES y devuelve la ruta temporal del archivo.
     */
    private function validarSubida(array $archivo): string
    {
        // Un input con name="archivo[]" manda listas: este método recibe un solo archivo.
        if (!isset($archivo['error'], $archivo['tmp_name'], $archivo['name']) || is_array($archivo['error'])) {
            throw new ArchivoInvalidoException('No llegó ningún archivo.');
        }

        switch ((int) $archivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:    // Superó upload_max_filesize de php.ini
            case UPLOAD_ERR_FORM_SIZE:   // Superó el MAX_FILE_SIZE del formulario
                throw new ArchivoInvalidoException($this->mensajeTamanio());
            case UPLOAD_ERR_NO_FILE:
                throw new ArchivoInvalidoException('No se eligió ningún archivo.');
            case UPLOAD_ERR_PARTIAL:
                throw new ArchivoInvalidoException('El archivo llegó incompleto. Probá subirlo de nuevo.');
            default:                     // Sin carpeta temporal, sin permiso de escritura, una extensión de PHP lo frenó
                throw new \RuntimeException('El servidor no pudo recibir el archivo (código ' . (int) $archivo['error'] . ').');
        }

        $temporal = (string) $archivo['tmp_name'];

        // Solo se aceptan archivos que llegaron por una subida HTTP de este pedido.
        // Evita que alguien haga "guardar" de un archivo cualquiera del servidor.
        if (!is_uploaded_file($temporal)) {
            throw new \RuntimeException('El archivo no llegó por una subida HTTP.');
        }

        return $temporal;
    }

    /**
     * Valida una ruta relativa y la devuelve normalizada (con '/').
     * Cada parte solo puede tener letras, números, punto, guion y guion bajo,
     * y no puede ser '.' ni '..'. Así nunca sale de la carpeta del almacén.
     */
    private function normalizar(string $rutaRelativa): string
    {
        $partes = preg_split('#[/\\\\]+#', trim($rutaRelativa, '/\\')) ?: [];

        if ($partes === [] || $partes === ['']) {
            throw new \InvalidArgumentException('La ruta del almacén está vacía.');
        }

        foreach ($partes as $parte) {
            if ($parte === '.' || $parte === '..' || !preg_match('/^[A-Za-z0-9._-]+$/', $parte)) {
                throw new \InvalidArgumentException('Ruta del almacén no válida.');
            }
        }

        return implode('/', $partes);
    }

    /**
     * Ruta absoluta de algo que ya existe en el almacén, o null si no existe.
     * Además de normalizar, compara la ruta real (realpath, que resuelve enlaces)
     * con la raíz: un enlace simbólico que apunte afuera tampoco pasa.
     */
    private function rutaExistente(string $rutaRelativa): ?string
    {
        $real = realpath($this->raiz . '/' . $this->normalizar($rutaRelativa));

        if ($real === false) {
            return null;
        }

        $real = str_replace('\\', '/', $real);
        if (!str_starts_with($real, $this->raiz . '/')) {
            throw new \InvalidArgumentException('Ruta del almacén no válida.');
        }

        return $real;
    }

    /**
     * Deja un nombre de archivo seguro para mostrar y para un encabezado HTTP:
     * sin carpetas (algunos navegadores mandan la ruta completa), sin caracteres
     * de control, comillas ni barras, y de 200 caracteres como máximo.
     * Si queda vacío, usa "archivo" (+ la extensión, si se pasa).
     */
    private function sanearNombre(string $nombre, ?string $extension): string
    {
        // Los navegadores mandan el nombre en UTF-8, pero otros clientes (ej. curl
        // en una consola de Windows) lo mandan en Windows-1252. Sin convertirlo,
        // la limpieza de abajo (con /u) descartaría el nombre entero.
        if (!mb_check_encoding($nombre, 'UTF-8')) {
            $nombre = mb_convert_encoding($nombre, 'UTF-8', 'Windows-1252');
        }

        // Sin carpetas: algunos navegadores mandan "C:\fakepath\archivo.pdf".
        // Se corta a mano porque basename() depende del locale y puede romper caracteres UTF-8.
        $nombre = str_replace('\\', '/', $nombre);
        $nombre = substr($nombre, (int) strrpos('/' . $nombre, '/'));
        $nombre = preg_replace('/[\x00-\x1F\x7F"\\\\\/]/u', '', $nombre) ?? '';
        $nombre = trim(mb_substr($nombre, 0, 200));

        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            $nombre = 'archivo' . ($extension === null ? '' : '.' . $extension);
        }

        return $nombre;
    }

    /** Ej.: "El archivo supera los 5 MB." */
    private function mensajeTamanio(): string
    {
        $mb = round($this->tamanioMaximo / (1024 * 1024), 1);
        $texto = rtrim(rtrim(number_format($mb, 1, ',', ''), '0'), ',');

        return "El archivo supera los {$texto} MB.";
    }

    /** Ej.: "Solo se aceptan archivos PDF, JPG o PNG." */
    private function mensajeTipos(): string
    {
        $extensiones = array_values(array_unique(array_map('strtoupper', $this->tiposPermitidos)));
        $ultima = array_pop($extensiones);
        $lista = $extensiones === [] ? $ultima : implode(', ', $extensiones) . ' o ' . $ultima;

        return "Solo se aceptan archivos {$lista}.";
    }

    /** Convierte un valor de php.ini como "8M" o "1G" a bytes. */
    private static function bytesDeIni(string $valor): int
    {
        $valor = trim($valor);
        $numero = (int) $valor;

        return match (strtoupper(substr($valor, -1))) {
            'G'     => $numero * 1024 * 1024 * 1024,
            'M'     => $numero * 1024 * 1024,
            'K'     => $numero * 1024,
            default => $numero,
        };
    }
}
