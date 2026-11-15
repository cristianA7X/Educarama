<?php
// src/Controller/DocumentoController.php
declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Service\DocumentoService;
use Educarama\Service\UsuarioService;

final class DocumentoController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly DocumentoService $documentos
    ) {
        parent::__construct($usuarioService);
    }

    public function descargar(array $get): Respuesta
    {
        $documento = $this->documentos->buscar($this->usuario()->id, self::entero($get, 'id'));
        return Respuesta::archivo($documento->rutaArchivo, $documento->nombreOriginal, self::booleano($get, 'ver'));
    }

    public function subir(array $post, array $files): Respuesta
    {
        $idProtagonista = self::entero($post, 'id_protagonista');
        
        $archivo = isset($files['archivo']['error']) && !is_array($files['archivo']['error'])
            ? $files['archivo']
            : ['error' => UPLOAD_ERR_NO_FILE];
            
        $this->documentos->subir(
            $this->usuario()->id,
            $idProtagonista,
            $archivo,
            self::texto($post, 'tipo_documento'),
            self::texto($post, 'descripcion'),
        );
        
        Flash::guardar(Flash::EXITO, 'Documento subido.');
        return Respuesta::exito(redireccion: self::perfil($idProtagonista));
    }

    public function actualizar(array $post): Respuesta
    {
        $this->documentos->actualizar(
            $this->usuario()->id,
            self::entero($post, 'id_documento'),
            self::texto($post, 'tipo_documento'),
            self::texto($post, 'descripcion'),
        );
        return Respuesta::exito(mensaje: 'Cambios guardados.');
    }

    public function eliminar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $documento = $this->documentos->buscar($idUsuario, self::entero($post, 'id_documento'));
        $this->documentos->eliminar($idUsuario, (int) $documento->id);
        Flash::guardar(Flash::EXITO, 'Documento eliminado.');
        return Respuesta::exito(redireccion: self::perfil($documento->idProtagonista));
    }

    private static function perfil(int $idProtagonista): string
    {
        return url('views/pages/perfil_protagonista.php?id=' . $idProtagonista) . '#documentos';
    }
}