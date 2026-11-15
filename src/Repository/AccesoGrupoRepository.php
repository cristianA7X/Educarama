<?php // src/Repository/AccesoGrupoRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AccesoGrupo;
use Educarama\Repository\Vista\UsuarioConAcceso;
use PDO;

final class AccesoGrupoRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscar(int $idUsuario, int $idGrupo): ?AccesoGrupo
    {
        return $this->buscarUno(
            'SELECT * FROM acceso_grupo WHERE id_usuario = ? AND id_grupo = ?',
            [$idUsuario, $idGrupo],
            AccesoGrupo::class,
        );
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        return $this->listar(
            'SELECT * FROM acceso_grupo WHERE id_usuario = ? ORDER BY id_grupo',
            [$idUsuario],
            AccesoGrupo::class,
        );
    }

    public function listarPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT * FROM acceso_grupo WHERE id_grupo = ? ORDER BY id_usuario',
            [$idGrupo],
            AccesoGrupo::class,
        );
    }

    public function listarUsuariosPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT ag.id_usuario, u.nombre AS usuario_nombre, u.email AS usuario_email, r.nivel AS rol_nivel
               FROM acceso_grupo ag
               JOIN usuario u ON u.id = ag.id_usuario
               JOIN rol r     ON r.id = ag.id_rol
              WHERE ag.id_grupo = ?
              ORDER BY r.nivel DESC, u.nombre',
            [$idGrupo],
            UsuarioConAcceso::class,
        );
    }

    public function insertar(AccesoGrupo $acceso): void
    {
        $this->ejecutar(
            'INSERT INTO acceso_grupo (id_usuario, id_grupo, id_rol) VALUES (:id_usuario, :id_grupo, :id_rol)',
            ['id_usuario' => $acceso->idUsuario, 'id_grupo' => $acceso->idGrupo, 'id_rol' => $acceso->idRol],
        );
    }

    public function actualizar(AccesoGrupo $acceso): void
    {
        $this->ejecutar(
            'UPDATE acceso_grupo SET id_rol = :id_rol WHERE id_usuario = :id_usuario AND id_grupo = :id_grupo',
            ['id_rol' => $acceso->idRol, 'id_usuario' => $acceso->idUsuario, 'id_grupo' => $acceso->idGrupo],
        );
    }

    public function eliminar(int $idUsuario, int $idGrupo): void
    {
        $this->ejecutar('DELETE FROM acceso_grupo WHERE id_usuario = ? AND id_grupo = ?', [$idUsuario, $idGrupo]);
    }
}