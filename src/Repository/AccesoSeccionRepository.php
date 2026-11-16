<?php // src/Repository/AccesoSeccionRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AccesoSeccion;
use Educarama\Repository\Vista\UsuarioConAcceso;
use PDO;

final class AccesoSeccionRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idUsuario, int $idSeccion): ?AccesoSeccion
    {
        return $this->buscarUno(
            'SELECT * FROM acceso_seccion WHERE id_usuario = ? AND id_seccion = ?',
            [$idUsuario, $idSeccion],
            AccesoSeccion::class,
        );
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        return $this->listar(
            'SELECT * FROM acceso_seccion WHERE id_usuario = ? ORDER BY id_seccion',
            [$idUsuario],
            AccesoSeccion::class,
        );
    }

    public function listarPorSeccion(int $idSeccion): array
    {
        return $this->listar(
            'SELECT * FROM acceso_seccion WHERE id_seccion = ? ORDER BY id_usuario',
            [$idSeccion],
            AccesoSeccion::class,
        );
    }

    public function listarUsuariosPorSeccion(int $idSeccion): array
    {
        return $this->listar(
            'SELECT a.id_usuario, u.nombre AS usuario_nombre, u.email AS usuario_email, r.nivel AS rol_nivel
               FROM acceso_seccion a
               JOIN usuario u ON u.id = a.id_usuario
               JOIN rol r     ON r.id = a.id_rol
              WHERE a.id_seccion = ?
              ORDER BY r.nivel DESC, u.nombre',
            [$idSeccion],
            UsuarioConAcceso::class,
        );
    }

    public function insertar(AccesoSeccion $acceso): void
    {
        $this->ejecutar(
            'INSERT INTO acceso_seccion (id_usuario, id_seccion, id_rol) VALUES (:id_usuario, :id_seccion, :id_rol)',
            ['id_usuario' => $acceso->idUsuario, 'id_seccion' => $acceso->idSeccion, 'id_rol' => $acceso->idRol],
        );
    }

    public function actualizar(AccesoSeccion $acceso): void
    {
        $this->ejecutar(
            'UPDATE acceso_seccion SET id_rol = :id_rol WHERE id_usuario = :id_usuario AND id_seccion = :id_seccion',
            ['id_rol' => $acceso->idRol, 'id_usuario' => $acceso->idUsuario, 'id_seccion' => $acceso->idSeccion],
        );
    }

    public function eliminar(int $idUsuario, int $idSeccion): void
    {
        $this->ejecutar(
            'DELETE FROM acceso_seccion WHERE id_usuario = ? AND id_seccion = ?',
            [$idUsuario, $idSeccion],
        );
    }
}