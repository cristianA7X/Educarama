<?php
// src/Service/DocumentoService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Almacen\AlmacenArchivos;
use Educarama\Almacen\ArchivoInvalidoException;
use Educarama\Model\Documento;
use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Protagonista;
use Educarama\Repository\DocumentoRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use PDO;

/**
 * Documentos de los protagonistas (fichas médicas, autorizaciones...).
 *
 * Usa dos capas:
 *  - El almacén genérico (Educarama\Almacen\AlmacenArchivos) guarda, envía y
 *    borra el archivo. No sabe qué es un protagonista.
 *  - Este Service decide quién puede hacer qué y mantiene la tabla `documento`.
 *
 * Permisos: ver y descargar, Ayudante; subir y editar tipo/descripción,
 * Responsable; eliminar, Jefe.
 *
 * Pertenencia: cada operación recorre documento → protagonista → sección y
 * controla el acceso del usuario a ESA sección (PermisoService ya resuelve al
 * Jefe de grupo). Si el usuario no tiene ningún acceso a la sección, se lanza
 * NoEncontradoException (404), no AccesoDenegadoException: así no se confirma
 * que el documento existe. Si tiene acceso pero no el rol (ej. un Ayudante que
 * quiere borrar), sí es AccesoDenegadoException (403): el documento ya lo puede ver.
 *
 * Descarga, en el Controller (después de este Service):
 *
 *   $documento = $servicio->buscar($idUsuario, $idDocumento);   // controla todo lo de arriba
 *   $almacen->enviar($documento->rutaArchivo, $documento->nombreOriginal, enLinea: true);
 *
 * enviar() solo muestra en línea las imágenes; un PDF siempre se descarga como adjunto.
 */
final class DocumentoService extends ServicioBase
{
    /**
     * Lo que entra en la columna `descripcion` (TEXT = 65.535 bytes): con
     * utf8mb4 un carácter ocupa hasta 4 bytes. No es una regla de negocio.
     */
    private const LARGO_DESCRIPCION = 16383;


    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly ProtagonistaRepository $protagonistas,
        private readonly DocumentoRepository $documentos,
        private readonly AlmacenArchivos $almacen
    ) {
        parent::__construct($conexion);
    }

    /**
     * Subcarpeta del almacén con los archivos de un protagonista. Es la única
     * regla de dónde van; la usa también ProtagonistaService al eliminar.
     */
    public static function carpetaDe(int $idProtagonista): string
    {
        return 'documentos/protagonista/' . $idProtagonista;
    }

    // -----------------------------------------------------------------
    // Lectura (Ayudante en adelante)
    // -----------------------------------------------------------------

    /** @return Documento[] Del más reciente al más viejo. */
    public function listarPorProtagonista(int $idUsuario, int $idProtagonista): array
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Ayudante);

        return $this->documentos->listarPorProtagonista($idProtagonista);
    }

    /** Un documento, para descargarlo o editarlo. Controla toda la cadena de pertenencia. */
    public function buscar(int $idUsuario, int $idDocumento): Documento
    {
        return $this->cargarDocumento($idUsuario, $idDocumento, NivelRol::Ayudante);
    }

    // -----------------------------------------------------------------
    // Escritura
    // -----------------------------------------------------------------

    /**
     * Para el Controller que recibe la subida: detecta cuando el pedido superó
     * post_max_size (PHP deja $_FILES vacío sin avisar) y lo convierte en el
     * mismo mensaje de "supera el tamaño máximo". Llamarlo antes de leer $_POST.
     *
     * @throws ReglaNegocioException
     */
    public function verificarLimiteDelServidor(): void
    {
        try {
            $this->almacen->verificarLimiteDelServidor();
        } catch (ArchivoInvalidoException $e) {
            throw new ReglaNegocioException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Sube un documento del protagonista (Responsable) y lo devuelve ya leído de
     * la base (con su fecha de subida).
     *
     * Orden: primero se guarda el archivo y después se inserta la fila. Si el
     * insert falla, se borra el archivo: nunca queda un archivo sin fila.
     *
     * @param array<string, mixed> $archivoSubido El elemento de $_FILES, ej. $_FILES['archivo']
     * @throws ReglaNegocioException si el archivo no es válido (tamaño, tipo) o un texto es muy largo
     */
    public function subir(
        int $idUsuario,
        int $idProtagonista,
        array $archivoSubido,
        ?string $tipoDocumento,
        ?string $descripcion,
    ): Documento {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);

        // Los textos se validan antes de tocar el disco.
        $tipoDocumento = Validacion::textoOpcional($tipoDocumento, 50, 'El tipo de documento');
        $descripcion = Validacion::textoOpcional($descripcion, self::LARGO_DESCRIPCION, 'La descripción');

        try {
            $guardado = $this->almacen->guardar($archivoSubido, self::carpetaDe($idProtagonista));
        } catch (ArchivoInvalidoException $e) {
            // Los Controllers atrapan ServicioException: se traduce, con el mismo mensaje.
            throw new ReglaNegocioException($e->getMessage(), 0, $e);
        }

        try {
            $id = $this->documentos->insertar(new Documento(
                id: null,
                idProtagonista: $idProtagonista,
                tipoDocumento: $tipoDocumento,
                descripcion: $descripcion,
                rutaArchivo: $guardado->rutaRelativa,
                nombreOriginal: $guardado->nombreOriginal,
                tipoMime: $guardado->tipoMime,
                tamanioBytes: $guardado->tamanioBytes,
                fechaSubida: null,
            ));
        } catch (\Throwable $e) {
            $this->almacen->borrar($guardado->rutaRelativa);
            throw $e;
        }

        return $this->documentos->buscarPorId($id)
            ?? throw new \LogicException("El documento $id no se encontró después de insertarlo.");
    }

    /** Cambia el tipo y la descripción (Responsable). El archivo no se toca. */
    public function actualizar(int $idUsuario, int $idDocumento, ?string $tipoDocumento, ?string $descripcion): void
    {
        $documento = $this->cargarDocumento($idUsuario, $idDocumento, NivelRol::Responsable);

        $documento->tipoDocumento = Validacion::textoOpcional($tipoDocumento, 50, 'El tipo de documento');
        $documento->descripcion = Validacion::textoOpcional($descripcion, self::LARGO_DESCRIPCION, 'La descripción');

        $this->documentos->actualizar($documento);
    }

    /**
     * Elimina el documento (Jefe). Orden: primero la fila y después el archivo.
     * Si borrar el archivo falla, la fila ya no está: el archivo queda sin fila
     * (nadie lo puede pedir), se anota en el log y no se le muestra error al usuario.
     */
    public function eliminar(int $idUsuario, int $idDocumento): void
    {
        $documento = $this->cargarDocumento($idUsuario, $idDocumento, NivelRol::Jefe);

        $this->documentos->eliminar($idDocumento);

        try {
            $this->almacen->borrar($documento->rutaArchivo);
        } catch (\Throwable $e) {
            error_log("No se pudo borrar el archivo del documento $idDocumento: " . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Pertenencia y permisos
    // -----------------------------------------------------------------

    /** Documento → protagonista → sección → acceso. */
    private function cargarDocumento(int $idUsuario, int $idDocumento, NivelRol $minimo): Documento
    {
        $documento = $this->documentos->buscarPorId($idDocumento)
            ?? throw new NoEncontradoException('El documento no existe.');

        $this->cargarProtagonista($idUsuario, $documento->idProtagonista, $minimo);

        return $documento;
    }

    /**
     * Protagonista → sección → acceso. Sin acceso a la sección: 404 (no se
     * confirma que existe). Con acceso pero sin el rol: 403.
     */
    private function cargarProtagonista(int $idUsuario, int $idProtagonista, NivelRol $minimo): Protagonista
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El documento no existe.');

        $nivel = $this->permisos->nivelEnSeccion($idUsuario, $protagonista->idSeccion);

        if ($nivel === null) {
            throw new NoEncontradoException('El documento no existe.');
        }
        if ($nivel->value < $minimo->value) {
            throw new AccesoDenegadoException('Necesitás el rol ' . $minimo->etiqueta() . ' en esta sección para hacer esto.');
        }

        return $protagonista;
    }
}
