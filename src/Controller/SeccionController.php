<?php
// src/Controller/SeccionController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Service\EventoService;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

final class SeccionController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly SeccionService $secciones,
        private readonly EventoService $eventos
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaSecciones(): Respuesta
    {
        return Respuesta::pagina([
            'grupos' => $this->secciones->misGrupos($this->usuario()->id),
        ]);
    }

    public function paginaPerfil(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::entero($get, 'id');

        $seccion = $this->secciones->encabezado($idUsuario, $idSeccion);

        return Respuesta::pagina([
            'seccion'  => $seccion,
            'conteos'  => $this->secciones->conteos($idUsuario, $idSeccion),
            'proximos' => $this->eventos->proximosPorSeccion($idUsuario, $idSeccion),
        ]);
    }

    public function paginaGrupo(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idGrupo = self::entero($get, 'id');

        return Respuesta::pagina([
            'grupo'    => $this->secciones->grupo($idUsuario, $idGrupo),
            'proximos' => $this->eventos->proximosPorGrupo($idUsuario, $idGrupo),
        ]);
    }
}