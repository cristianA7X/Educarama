<?php // src/Repository/UsuarioRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Usuario;
use PDO;

final class UsuarioRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Usuario
    {
        return $this->buscarUno('SELECT * FROM usuario WHERE id = ?', [$id], Usuario::class);
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        return $this->buscarUno('SELECT * FROM usuario WHERE email = ?', [$email], Usuario::class);
    }

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

    public function cambiarContrasenia(int $idUsuario, string $hash): void
    {
        $this->ejecutar(
            'UPDATE usuario SET contrasenia = ?, token_recuperacion = NULL WHERE id = ?',
            [$hash, $idUsuario],
        );
    }

    public function guardarToken(int $idUsuario, ?string $token): void
    {
        $this->ejecutar('UPDATE usuario SET token_recuperacion = ? WHERE id = ?', [$token, $idUsuario]);
    }
}