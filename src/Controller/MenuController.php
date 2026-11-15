<?php
// src/Controller/MenuController.php
declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

final class MenuController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly SeccionService $secciones
    ) {
        parent::__construct($usuarioService);
    }

    public function datosMenu(): Respuesta
    {
        $usuario = $this->usuario();
        return Respuesta::pagina([
            'usuario' => $usuario,
            'grupos'  => $this->secciones->misGrupos($usuario->id),
        ]);
    }
}