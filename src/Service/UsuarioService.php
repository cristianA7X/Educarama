<?php
// src/Service/UsuarioService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Helper\Csrf;
use Educarama\Helper\Sesion;
use Educarama\Model\Usuario;
use Educarama\Repository\UsuarioRepository;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Excepcion\SesionRequeridaException;
use Educarama\Service\Vista\UsuarioSesion;

/**
 * Cuentas de usuario: registro, inicio y cierre de sesión, usuario actual y
 * cambio de contraseña.
 *
 * ⚠️ Es el ÚNICO Service que toca $_SESSION (CLAUDE.md, "Sesión"). Los demás
 * reciben el id del usuario como parámetro: el Controller lo saca de acá con
 * exigirSesion()->id y se lo pasa.
 *
 * En la sesión se guarda solo el id del usuario. Sus datos (nombre, si es
 * admin) se vuelven a leer de la base en cada pedido, así un cambio se nota
 * enseguida y nunca se confía en datos viejos guardados en la sesión.
 *
 * Recuperar la contraseña olvidada queda para más adelante (hace falta envío
 * de emails): la columna usuario.token_recuperacion está reservada.
 */
final class UsuarioService extends ServicioBase
{
    /** Clave de $_SESSION con el id del usuario logueado. */
    private const CLAVE_SESION = 'id_usuario';

    /**
     * Hash de una contraseña al azar que nadie conoce. Cuando el email no existe,
     * se compara contra este hash igual: así el login tarda lo mismo exista o no
     * el email, y no se puede averiguar qué emails están registrados midiendo tiempos.
     */
    private const HASH_FICTICIO = '$2y$10$1LOl62OW72R2FjrKRi2mxusy6RM5GZC9DhDtzgS30MadmAPWCslCe';

    /** El mismo mensaje para email inexistente y contraseña incorrecta (no revela cuál falló). */
    private const ERROR_LOGIN = 'Email o contraseña incorrectos.';

    private UsuarioRepository $usuarios;

    public function __construct(?UsuarioRepository $usuarios = null)
    {
        parent::__construct();
        $this->usuarios = $usuarios ?? new UsuarioRepository();
    }

    // -----------------------------------------------------------------
    // Registro e inicio de sesión
    // -----------------------------------------------------------------

    /**
     * Crea la cuenta (sin accesos: después envía una solicitud de alta) y deja
     * al usuario logueado.
     *
     * @throws ReglaNegocioException
     */
    public function registrar(string $nombre, string $email, string $contrasenia): UsuarioSesion
    {
        $nombre = Validacion::textoOpcional($nombre, 100, 'El nombre')
            ?? throw new ReglaNegocioException('El nombre es obligatorio.');
        $email = $this->normalizarEmail($email);
        $this->validarContrasenia($contrasenia);

        if ($this->usuarios->buscarPorEmail($email) !== null) {
            throw new ReglaNegocioException('Ya existe una cuenta con ese email.');
        }

        $usuario = new Usuario(
            id: null,
            nombre: $nombre,
            email: $email,
            contrasenia: password_hash($contrasenia, PASSWORD_DEFAULT),
            esAdmin: false,
            emailValidado: false,
            tokenRecuperacion: null,
        );
        $this->usuarios->insertar($usuario);

        return $this->abrirSesion($usuario);
    }

    /**
     * Verifica email y contraseña y abre la sesión.
     *
     * @throws ReglaNegocioException "Email o contraseña incorrectos." (siempre el mismo)
     */
    public function iniciarSesion(string $email, string $contrasenia): UsuarioSesion
    {
        $usuario = $this->usuarios->buscarPorEmail(mb_strtolower(trim($email)));

        if ($usuario === null) {
            password_verify($contrasenia, self::HASH_FICTICIO);  // Mismo tiempo de respuesta
            throw new ReglaNegocioException(self::ERROR_LOGIN);
        }

        if (!password_verify($contrasenia, $usuario->contrasenia)) {
            throw new ReglaNegocioException(self::ERROR_LOGIN);
        }

        // Si PHP cambió el algoritmo o el costo por defecto, se aprovecha que
        // tenemos la contraseña en claro para guardar el hash actualizado.
        if (password_needs_rehash($usuario->contrasenia, PASSWORD_DEFAULT)) {
            $this->usuarios->cambiarContrasenia((int) $usuario->id, password_hash($contrasenia, PASSWORD_DEFAULT));
        }

        return $this->abrirSesion($usuario);
    }

    /** Cierra la sesión: borra sus datos, la cookie y la sesión del servidor. */
    public function cerrarSesion(): void
    {
        Sesion::iniciar();
        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }

        session_destroy();
    }

    // -----------------------------------------------------------------
    // Usuario actual
    // -----------------------------------------------------------------

    /** El usuario logueado, o null si no hay sesión. */
    public function usuarioActual(): ?UsuarioSesion
    {
        Sesion::iniciar();
        $id = $_SESSION[self::CLAVE_SESION] ?? null;

        if (!is_int($id)) {
            return null;
        }

        $usuario = $this->usuarios->buscarPorId($id);
        if ($usuario === null) {
            // La cuenta ya no existe: la sesión no sirve más.
            unset($_SESSION[self::CLAVE_SESION]);
            return null;
        }

        return UsuarioSesion::desdeUsuario($usuario);
    }

    /**
     * El usuario logueado; si no hay, corta. Uso típico al principio de un Controller:
     *
     *   $usuario = $usuarioService->exigirSesion();          // si no hay sesión → al login
     *   $servicio->hacerAlgo($usuario->id, ...);
     *
     * @throws SesionRequeridaException
     */
    public function exigirSesion(): UsuarioSesion
    {
        return $this->usuarioActual()
            ?? throw new SesionRequeridaException('Tenés que iniciar sesión para ver esta página.');
    }

    // -----------------------------------------------------------------
    // Contraseña
    // -----------------------------------------------------------------

    /**
     * Cambia la contraseña del usuario logueado. Pide la actual, así alguien
     * que encuentre la sesión abierta no puede cambiarla.
     *
     * @throws ReglaNegocioException
     */
    public function cambiarContrasenia(int $idUsuario, string $actual, string $nueva): void
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario)
            ?? throw new SesionRequeridaException('Tenés que iniciar sesión para ver esta página.');

        if (!password_verify($actual, $usuario->contrasenia)) {
            throw new ReglaNegocioException('La contraseña actual no es correcta.');
        }

        $this->validarContrasenia($nueva);
        $this->usuarios->cambiarContrasenia($idUsuario, password_hash($nueva, PASSWORD_DEFAULT));

        // Id de sesión nuevo: una copia vieja de la cookie deja de servir.
        Sesion::iniciar();
        session_regenerate_id(true);
    }

    // -----------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------

    /**
     * Guarda al usuario en la sesión. El id de sesión se renueva al loguearse
     * (un id que alguien conociera de antes deja de servir), y también el token CSRF.
     */
    private function abrirSesion(Usuario $usuario): UsuarioSesion
    {
        Sesion::iniciar();
        session_regenerate_id(true);
        $_SESSION = [self::CLAVE_SESION => (int) $usuario->id];
        Csrf::regenerar();

        return UsuarioSesion::desdeUsuario($usuario);
    }

    /** Email obligatorio, válido, sin espacios y en minúsculas (así no hay dos cuentas "Ana@" y "ana@"). */
    private function normalizarEmail(string $email): string
    {
        $email = Validacion::emailOpcional($email)
            ?? throw new ReglaNegocioException('El email es obligatorio.');

        return mb_strtolower($email);
    }

    /**
     * Reglas de la contraseña nueva (registro y cambio).
     *
     * Hay dos versiones. Está activa la B porque en las pruebas se usan
     * contraseñas cortas; para producción va la A.
     */
    private function validarContrasenia(string $contrasenia): void
    {
        // --- Versión B (ACTIVA): al menos 6 caracteres, como el legacy. ---
        if (mb_strlen($contrasenia) < 6) {
            throw new ReglaNegocioException('La contraseña tiene que tener al menos 6 caracteres.');
        }

        // --- Versión A (PRODUCCIÓN): al menos 8 caracteres y como máximo 72 bytes. ---
        // bcrypt (lo que usa PASSWORD_DEFAULT) ignora todo lo que pase de 72 bytes:
        // dos contraseñas que solo difieren después del byte 72 serían la misma.
        // Para activarla: borrar el bloque de la versión B y descomentar este.
        //
        // if (mb_strlen($contrasenia) < 8) {
        //     throw new ReglaNegocioException('La contraseña tiene que tener al menos 8 caracteres.');
        // }
        // if (strlen($contrasenia) > 72) {
        //     throw new ReglaNegocioException('La contraseña es demasiado larga (máximo 72 caracteres sin acentos).');
        // }
    }
}
