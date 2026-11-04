<?php
// src/Controller/UsuarioController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Service\Excepcion\ReglaNegocioException;

/**
 * Cuentas: login, registro, logout y "Mi cuenta" (cambiar la contraseña).
 *
 * Páginas (GET, desde views/pages/):
 *   paginaLogin      login.php       Si ya hay sesión, va directo al destino.
 *   paginaRegistro   registro.php    Si ya hay sesión, va al inicio.
 *   paginaMiCuenta   mi_cuenta.php   Nombre y email (solo lectura) + cambiar contraseña.
 *
 * Acciones (POST por fetch a public/api.php):
 *   usuario.iniciarSesion        email, contrasenia, volver (opcional)
 *   usuario.registrar            nombre, email, contrasenia, contrasenia_repetida
 *   usuario.cerrarSesion         —
 *   usuario.cambiarContrasenia   actual, nueva, nueva_repetida
 */
final class UsuarioController extends Controlador
{
    /** Adónde se va después del login si no hay "volver". */
    private const INICIO = 'views/pages/inicio.php';

    /** Adónde va una cuenta recién creada: todavía no tiene accesos. */
    private const SOLICITUD_ALTA = 'views/pages/solicitud_alta.php';

    // -----------------------------------------------------------------
    // Páginas
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $get volver (opcional) */
    public function paginaLogin(array $get): Respuesta
    {
        $volver = self::rutaInterna(self::texto($get, 'volver'));

        if ($this->usuarioService->usuarioActual() !== null) {
            return Respuesta::redireccion(url($volver ?? self::INICIO));
        }

        // La vista lo manda de vuelta en un campo oculto del formulario.
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
        return Respuesta::pagina(['usuario' => $this->usuario()]);
    }

    // -----------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
    public function iniciarSesion(array $post): Respuesta
    {
        $this->usuarioService->iniciarSesion(self::texto($post, 'email'), self::texto($post, 'contrasenia'));

        $volver = self::rutaInterna(self::texto($post, 'volver'));
        return Respuesta::exito(redireccion: url($volver ?? self::INICIO));
    }

    /** @param array<string, mixed> $post */
    public function registrar(array $post): Respuesta
    {
        $contrasenia = self::texto($post, 'contrasenia');
        self::exigirIguales($contrasenia, self::texto($post, 'contrasenia_repetida'));

        $this->usuarioService->registrar(self::texto($post, 'nombre'), self::texto($post, 'email'), $contrasenia);

        Flash::guardar(Flash::EXITO, 'Cuenta creada. Para empezar, pedí el alta de tu grupo.');
        return Respuesta::exito(redireccion: url(self::SOLICITUD_ALTA));
    }

    /** Cierra la sesión. Es POST con CSRF: un link GET lo podría disparar otra página. */
    public function cerrarSesion(): Respuesta
    {
        $this->usuarioService->cerrarSesion();
        return Respuesta::exito(redireccion: url('views/pages/login.php'));
    }

    /** @param array<string, mixed> $post */
    public function cambiarContrasenia(array $post): Respuesta
    {
        $nueva = self::texto($post, 'nueva');
        self::exigirIguales($nueva, self::texto($post, 'nueva_repetida'));

        $this->usuarioService->cambiarContrasenia($this->usuario()->id, self::texto($post, 'actual'), $nueva);

        return Respuesta::exito(mensaje: 'Contraseña cambiada.');
    }

    // -----------------------------------------------------------------

    /**
     * El formulario pide escribir la contraseña dos veces, para no guardar una
     * con un error de tipeo. Es un control del formulario, no una regla del Service.
     *
     * @throws ReglaNegocioException
     */
    private static function exigirIguales(string $contrasenia, string $repetida): void
    {
        if ($contrasenia !== $repetida) {
            throw new ReglaNegocioException('Las contraseñas no coinciden.');
        }
    }
}
