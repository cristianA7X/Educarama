<?php

class RegistrarUsuarioService
{
    private UsuarioRepository $usuarioRepository;

    public function __construct(UsuarioRepository $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    public function registrar(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $email = trim($datos['email'] ?? '');
        $password = trim($datos['password'] ?? '');

        if ($nombre === '') {
            return [
                'status' => 'error',
                'message' => 'El nombre es obligatorio.'
            ];
        }

        if ($email === '') {
            return [
                'status' => 'error',
                'message' => 'El correo electrónico es obligatorio.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => 'El correo electrónico no es válido.'
            ];
        }

        if ($password === '') {
            return [
                'status' => 'error',
                'message' => 'La contraseña es obligatoria.'
            ];
        }

        if (strlen($password) < 6) {
            return [
                'status' => 'error',
                'message' => 'La contraseña debe tener al menos 6 caracteres.'
            ];
        }

        $usuarioExistente = $this->usuarioRepository->buscarPorEmail($email);

        if ($usuarioExistente) {
            return [
                'status' => 'error',
                'message' => 'Ya existe un usuario registrado con ese correo.'
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $this->usuarioRepository->crear([
            'nombre' => $nombre,
            'email' => $email,
            'password' => $passwordHash
        ]);

        return [
            'status' => 'success',
            'message' => 'Usuario registrado correctamente.'
        ];
    }
}