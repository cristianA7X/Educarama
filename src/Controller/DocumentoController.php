<?php
// src/Controller/DocumentoController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Service\DocumentoService;
use Educarama\Service\UsuarioService;

/**
 * Documentos de los protagonistas. Se ven y se suben desde el perfil del
 * protagonista: no tienen página propia.
 *
 * Descarga (GET, por public/descargar.php, no por api.php: devuelve un archivo, no JSON):
 *   descargar   descargar.php?id=        Ayudante. Adjunto siempre.
 *               descargar.php?id=&ver=1  Las imágenes se muestran en el navegador;
 *                                        un PDF baja como adjunto igual (lo decide el almacén).
 *
 * Acciones (POST por fetch a public/api.php):
 *   documento.subir       id_protagonista, archivo (FormData), tipo_documento, descripcion  → al perfil
 *   documento.actualizar  id_documento, tipo_documento, descripcion
 *   documento.eliminar    id_documento                                                     → al perfil (Jefe)
 *
 * Nunca hay un link directo al archivo (CLAUDE.md, "Archivos subidos"):
 * primero el Service controla la pertenencia y el permiso, después el almacén lo envía.
 */
final class DocumentoController extends Controlador
{
    private DocumentoService $documentos;

    public function __construct(?UsuarioService $usuarioService = null, ?DocumentoService $documentos = null)
    {
        parent::__construct($usuarioService);
        $this->documentos = $documentos ?? new DocumentoService();
    }

    /** @param array<string, mixed> $get id, ver (opcional) */
    public function descargar(array $get): Respuesta
    {
        $documento = $this->documentos->buscar($this->usuario()->id, self::entero($get, 'id'));  // 404 si no lo puede ver

        return Respuesta::archivo($documento->rutaArchivo, $documento->nombreOriginal, self::booleano($get, 'ver'));
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public function subir(array $post, array $files): Respuesta
    {
        $idProtagonista = self::entero($post, 'id_protagonista');

        // Si no vino el campo (o vino como lista), el almacén responde
        // "No se recibió ningún archivo" con UPLOAD_ERR_NO_FILE.
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

    /** Cambia tipo y descripción; el archivo no se toca. @param array<string, mixed> $post */
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

    /** @param array<string, mixed> $post */
    public function eliminar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;

        // Se busca antes de borrar, para saber a qué perfil volver.
        $documento = $this->documentos->buscar($idUsuario, self::entero($post, 'id_documento'));
        $this->documentos->eliminar($idUsuario, (int) $documento->id);

        Flash::guardar(Flash::EXITO, 'Documento eliminado.');
        return Respuesta::exito(redireccion: self::perfil($documento->idProtagonista));
    }

    private static function perfil(int $idProtagonista): string
    {
        return url('views/pages/perfil_protagonista.php?id=' . $idProtagonista);
    }
}
