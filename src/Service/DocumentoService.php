<?php // src/Service/DocumentoService.php
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

final class DocumentoService extends ServicioBase
{
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

    public static function carpetaDe(int $idProtagonista): string
    {
        return 'documentos/protagonista/' . $idProtagonista;
    }

    public function listarPorProtagonista(int $idUsuario, int $idProtagonista): array
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Ayudante);
        return $this->documentos->listarPorProtagonista($idProtagonista);
    }

    public function buscar(int $idUsuario, int $idDocumento): Documento
    {
        return $this->cargarDocumento($idUsuario, $idDocumento, NivelRol::Ayudante);
    }

    public function verificarLimiteDelServidor(): void
    {
        try {
            $this->almacen->verificarLimiteDelServidor();
        } catch (ArchivoInvalidoException $e) {
            throw new ReglaNegocioException($e->getMessage(), 0, $e);
        }
    }

    public function subir(
        int $idUsuario,
        int $idProtagonista,
        array $archivoSubido,
        ?string $tipoDocumento,
        ?string $descripcion,
    ): Documento {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);
        
        $tipoDocumento = Validacion::textoOpcional($tipoDocumento, 50, 'El tipo de documento');
        $descripcion = Validacion::textoOpcional($descripcion, self::LARGO_DESCRIPCION, 'La descripción');
        
        try {
            $guardado = $this->almacen->guardar($archivoSubido, self::carpetaDe($idProtagonista));
        } catch (ArchivoInvalidoException $e) {
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

    public function actualizar(int $idUsuario, int $idDocumento, ?string $tipoDocumento, ?string $descripcion): void
    {
        $documento = $this->cargarDocumento($idUsuario, $idDocumento, NivelRol::Responsable);
        $documento->tipoDocumento = Validacion::textoOpcional($tipoDocumento, 50, 'El tipo de documento');
        $documento->descripcion = Validacion::textoOpcional($descripcion, self::LARGO_DESCRIPCION, 'La descripción');
        $this->documentos->actualizar($documento);
    }

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

    private function cargarDocumento(int $idUsuario, int $idDocumento, NivelRol $minimo): Documento
    {
        $documento = $this->documentos->buscarPorId($idDocumento)
            ?? throw new NoEncontradoException('El documento no existe.');
        $this->cargarProtagonista($idUsuario, $documento->idProtagonista, $minimo);
        return $documento;
    }

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