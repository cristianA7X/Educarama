<?php
// src/Controller/UsuarioController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

final class UsuarioController extends Controlador
{
    private const INICIO = 'views/pages/inicio.php';
    private const SOLICITUD_ALTA = 'views/pages/solicitud_alta.php';

    public function __construct(
        UsuarioService $usuarioService,
        private readonly SeccionService $secciones
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaLogin(array $get): Respuesta
    {
        $volver = self::rutaInterna(self::texto($get, 'volver'));

        if ($this->usuarioService->usuarioActual() !== null) {
            return Respuesta::redireccion(url($volver ?? self::INICIO));
        }

        return Respuesta::pagina(['volver' => $volver]);
    }

    public function paginaRegistro(): Respuesta
    {
        if ($this->usuarioService->usuarioActual() !== null) {
            return Respuesta::redireccion(url(self::INICIO));
        }
        return Respuesta::pagina();
    }

    public function paginaMiCuenta(): Respuesta
    {
        $usuario = $this->usuario();
        return Respuesta::pagina([
            'usuario' => $usuario,
            'grupos'  => $this->secciones->misGrupos($usuario->id),
        ]);
    }

    public function iniciarSesion(array $post): Respuesta
    {
        // 1. Ahora iniciarSesion devuelve un array con [usuario, token]
        $resultado = $this->usuarioService->iniciarSesion(self::texto($post, 'email'), self::texto($post, 'contrasenia'));

        $volver = self::rutaInterna(self::texto($post, 'volver'));
        
        // 2. Devolvemos el Token en los datos de Respuesta::exito para que la App Mobile lo tome.
        return Respuesta::exito(
            datos: ['token' => $resultado['token']], 
            redireccion: url($volver ?? self::INICIO)
        );
    }

    public function registrar(array $post): Respuesta
    {
        $contrasenia = self::texto($post, 'contrasenia');
        self::exigirIguales($contrasenia, self::texto($post, 'contrasenia_repetida'));

        $this->usuarioService->registrar(self::texto($post, 'nombre'), self::texto($post, 'email'), $contrasenia);

        Flash::guardar(Flash::EXITO, 'Cuenta creada. Para empezar, pedí el alta de tu grupo.');
        return Respuesta::exito(redireccion: url(self::SOLICITUD_ALTA));
    }

    public function cerrarSesion(): Respuesta
    {
        $this->usuarioService->cerrarSesion();
        return Respuesta::exito(redireccion: url('views/pages/login.php'));
    }

    public function cambiarContrasenia(array $post): Respuesta
    {
        $nueva = self::texto($post, 'nueva');
        self::exigirIguales($nueva, self::texto($post, 'nueva_repetida'));

        $this->usuarioService->cambiarContrasenia($this->usuario()->id, self::texto($post, 'actual'), $nueva);

        return Respuesta::exito(mensaje: 'Contraseña cambiada.');
    }

    private static function exigirIguales(string $contrasenia, string $repetida): void
    {
        if ($contrasenia !== $repetida) {
            throw new ReglaNegocioException('Las contraseñas no coinciden.');
        }
    }
}