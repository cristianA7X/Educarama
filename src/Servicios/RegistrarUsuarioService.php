<?php

require_once __DIR__ . '/src/Dominio/Model/Usuario.php';
// Requerimos la interfaz genérica de agregar y la de mostrar por ID (o podrías tener una de buscar por email)
require_once __DIR__ . '/src/Dominio/Repositorio/AgregarInterface.php';

class RegistrarUsuarioService {
    
    // Inyectamos el repositorio usando la interfaz (¡así mantenemos el código desacoplado!)
    private AgregarInterface $usuarioRepository;

    public function __construct(AgregarInterface $usuarioRepository) {
        $this->usuarioRepository = $usuarioRepository;
    }

    public function ejecutar(string $nombre, string $email, string $contraseniaPlana): void {
        // 1. Validaciones de negocio simples
        if (empty($email) || empty($contraseniaPlana)) {
            throw new Exception("El email y la contraseña son obligatorios.");
        }

        //validar mail existente
        if ($this->usuarioRepository instanceof UsuarioRepository) 
        {
            $usuarioExistente = $this->usuarioRepository->buscarPorEmail($email);
            if ($usuarioExistente) 
            {
                throw new Exception("El email ya está registrado.");
            }
        }

        // 2. Encriptar la contraseña (Regla de negocio/seguridad)
        $contraseniaHash = password_hash($contraseniaPlana, PASSWORD_BCRYPT);

        // 3. Crear el objeto del Dominio
        $nuevoUsuario = new Usuario(0, $nombre, $email, $contraseniaHash);

        // 4. Mandarlo a guardar usando el repositorio
        $this->usuarioRepository->agregar($nuevoUsuario);
    }
}
?>