<?php

require_once __DIR__ . '/src/Datos/UsuarioRepository.php';

class LoginService {
    private UsuarioRepository $usuarioRepo;

    public function __construct(UsuarioRepository $usuarioRepo) {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutar(string $email, string $contrasenia): array {
        $usuario = $this->usuarioRepo->buscarPorEmail($email);

        if (!$usuario) {
            throw new Exception("Credenciales inválidas.");
        }

        // Si guardaste las contraseñas sin encriptar (para probar), usá: if ($contrasenia !== $usuario->contrasenia)
        // Si usaste password_hash(), usá password_verify():
        if (!password_verify($contrasenia, $usuario->contrasenia)) {
            throw new Exception("Credenciales inválidas.");
        }

        // Retornamos los datos del usuario SIN la contraseña para que el front los use
        return [
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email
        ];
    }
}
?>