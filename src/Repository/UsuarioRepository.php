<?php
// src/Repository/UsuarioRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Usuario;

/**
 * Lectura y escritura de `usuario`: los educadores que entran al sistema.
 *
 * ⚠️ El Usuario que devuelve tiene el hash de la contraseña: el Service lo usa
 * para password_verify(), pero nunca se manda entero a una vista.
 *
 * La contraseña y el token de recuperación se escriben solo con sus métodos
 * propios (cambiarContrasenia y guardarToken), nunca con actualizar(). Así, un
 * Usuario armado desde un formulario de perfil no puede pisar el hash por accidente.
 *
 * No tiene eliminar: se resuelve cuando exista la baja lógica.
 */
final class UsuarioRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?Usuario
    {
        return $this->buscarUno('SELECT * FROM usuario WHERE id = ?', [$id], Usuario::class);
    }

    /**
     * Para el login y la recuperación de contraseña. El email es único.
     * El token se compara en el Service con hash_equals(), no acá en el WHERE.
     */
    public function buscarPorEmail(string $email): ?Usuario
    {
        return $this->buscarUno('SELECT * FROM usuario WHERE email = ?', [$email], Usuario::class);
    }

    /**
     * Guarda un usuario nuevo, le completa el id y lo devuelve.
     * $usuario->contrasenia tiene que venir ya hasheada con password_hash().
     * El token de recuperación arranca siempre vacío.
     */
    public function insertar(Usuario $usuario): int
    {
        $usuario->id = $this->insertarFila(
            'INSERT INTO usuario (nombre, email, contrasenia, es_admin, email_validado)
             VALUES (:nombre, :email, :contrasenia, :es_admin, :email_validado)',
            [
                'nombre'         => $usuario->nombre,
                'email'          => $usuario->email,
                'contrasenia'    => $usuario->contrasenia,
                'es_admin'       => (int) $usuario->esAdmin,
                'email_validado' => (int) $usuario->emailValidado,
            ],
        );

        return $usuario->id;
    }

    /** Actualiza los datos de la cuenta. NO toca la contraseña ni el token. */
    public function actualizar(Usuario $usuario): void
    {
        $this->ejecutar(
            'UPDATE usuario
                SET nombre = :nombre, email = :email, es_admin = :es_admin, email_validado = :email_validado
              WHERE id = :id',
            [
                'nombre'         => $usuario->nombre,
                'email'          => $usuario->email,
                'es_admin'       => (int) $usuario->esAdmin,
                'email_validado' => (int) $usuario->emailValidado,
                'id'             => $this->exigirId($usuario->id),
            ],
        );
    }

    /**
     * Guarda una contraseña nueva (ya hasheada) y borra el token de recuperación,
     * para que no se pueda volver a usar.
     */
    public function cambiarContrasenia(int $idUsuario, string $hash): void
    {
        $this->ejecutar(
            'UPDATE usuario SET contrasenia = ?, token_recuperacion = NULL WHERE id = ?',
            [$hash, $idUsuario],
        );
    }

    /** Guarda el código de recuperación de contraseña, o lo borra si se pasa null. */
    public function guardarToken(int $idUsuario, ?string $token): void
    {
        $this->ejecutar('UPDATE usuario SET token_recuperacion = ? WHERE id = ?', [$token, $idUsuario]);
    }
}
