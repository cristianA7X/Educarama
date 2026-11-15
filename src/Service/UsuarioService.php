<?php // src/Service/UsuarioService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Helper\Csrf;
use Educarama\Helper\Sesion;
use Educarama\Model\Usuario;
use Educarama\Repository\UsuarioRepository;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Excepcion\SesionRequeridaException;
use Educarama\Service\Vista\UsuarioSesion;
use PDO;

final class UsuarioService extends ServicioBase
{
    private const CLAVE_SESION = 'id_usuario';
    private const HASH_FICTICIO = '$2y$10$1LOl62OW72R2FjrKRi2mxusy6RM5GZC9DhDtzgS30MadmAPWCslCe';
    private const ERROR_LOGIN = 'Email o contraseña incorrectos.';

    public function __construct(
        PDO $conexion,
        private readonly UsuarioRepository $usuarios
    ) {
        parent::__construct($conexion);
    }

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

    public function iniciarSesion(string $email, string $contrasenia): UsuarioSesion
    {
        $usuario = $this->usuarios->buscarPorEmail(mb_strtolower(trim($email)));
        if ($usuario === null) {
            password_verify($contrasenia, self::HASH_FICTICIO);
            throw new ReglaNegocioException(self::ERROR_LOGIN);
        }
        if (!password_verify($contrasenia, $usuario->contrasenia)) {
            throw new ReglaNegocioException(self::ERROR_LOGIN);
        }
        if (password_needs_rehash($usuario->contrasenia, PASSWORD_DEFAULT)) {
            $this->usuarios->cambiarContrasenia((int) $usuario->id, password_hash($contrasenia, PASSWORD_DEFAULT));
        }
        return $this->abrirSesion($usuario);
    }

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

    public function usuarioActual(): ?UsuarioSesion
    {
        Sesion::iniciar();
        $id = $_SESSION[self::CLAVE_SESION] ?? null;
        if (!is_int($id)) {
            return null;
        }
        $usuario = $this->usuarios->buscarPorId($id);
        if ($usuario === null) {
            unset($_SESSION[self::CLAVE_SESION]);
            return null;
        }
        return UsuarioSesion::desdeUsuario($usuario);
    }

    public function exigirSesion(): UsuarioSesion
    {
        return $this->usuarioActual()
            ?? throw new SesionRequeridaException('Tenés que iniciar sesión para ver esta página.');
    }

    public function cambiarContrasenia(int $idUsuario, string $actual, string $nueva): void
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario)
            ?? throw new SesionRequeridaException('Tenés que iniciar sesión para ver esta página.');
        if (!password_verify($actual, $usuario->contrasenia)) {
            throw new ReglaNegocioException('La contraseña actual no es correcta.');
        }
        $this->validarContrasenia($nueva);
        $this->usuarios->cambiarContrasenia($idUsuario, password_hash($nueva, PASSWORD_DEFAULT));
        Sesion::iniciar();
        session_regenerate_id(true);
    }

    private function abrirSesion(Usuario $usuario): UsuarioSesion
    {
        Sesion::iniciar();
        session_regenerate_id(true);
        $_SESSION = [self::CLAVE_SESION => (int) $usuario->id];
        Csrf::regenerar();
        return UsuarioSesion::desdeUsuario($usuario);
    }

    private function normalizarEmail(string $email): string
    {
        $email = Validacion::emailOpcional($email)
            ?? throw new ReglaNegocioException('El email es obligatorio.');
        return mb_strtolower($email);
    }

    private function validarContrasenia(string $contrasenia): void
    {
        if (mb_strlen($contrasenia) < 6) {
            throw new ReglaNegocioException('La contraseña tiene que tener al menos 6 caracteres.');
        }
    }
}