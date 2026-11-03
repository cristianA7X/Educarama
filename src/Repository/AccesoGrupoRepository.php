<?php
// src/Repository/AccesoGrupoRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AccesoGrupo;
use Educarama\Repository\Vista\UsuarioConAcceso;

/**
 * Lectura y escritura de `acceso_grupo`: qué rol tiene un usuario sobre un grupo.
 * No tiene id propio: cada fila se identifica por id_usuario + id_grupo.
 *
 * Este repositorio solo guarda filas. Las reglas de permisos (quién puede dar
 * o cambiar un rol, y que el Jefe de grupo es Jefe de todas sus secciones sin
 * filas en acceso_seccion) las aplica el Service.
 */
final class AccesoGrupoRepository extends RepositorioBase
{
    public function buscar(int $idUsuario, int $idGrupo): ?AccesoGrupo
    {
        return $this->buscarUno(
            'SELECT * FROM acceso_grupo WHERE id_usuario = ? AND id_grupo = ?',
            [$idUsuario, $idGrupo],
            AccesoGrupo::class,
        );
    }

    /** @return AccesoGrupo[] Los grupos a los que accede un usuario. */
    public function listarPorUsuario(int $idUsuario): array
    {
        return $this->listar(
            'SELECT * FROM acceso_grupo WHERE id_usuario = ? ORDER BY id_grupo',
            [$idUsuario],
            AccesoGrupo::class,
        );
    }

    /** @return AccesoGrupo[] Los usuarios con acceso a un grupo. */
    public function listarPorGrupo(int $idGrupo): array
    {
        return $this->listar(
            'SELECT * FROM acceso_grupo WHERE id_grupo = ? ORDER BY id_usuario',
            [$idGrupo],
            AccesoGrupo::class,
        );
    }

    /**
     * Los usuarios con acceso al grupo, con nombre, email y rol, para la pantalla
     * de gestión. Primero los de mayor rol, después por nombre.
     *
     * @return UsuarioConAcceso[]
     */
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

    /** Da acceso a un usuario. Si ya tenía acceso a ese grupo, la base lanza una excepción (PK duplicada). */
    public function insertar(AccesoGrupo $acceso): void
    {
        $this->ejecutar(
            'INSERT INTO acceso_grupo (id_usuario, id_grupo, id_rol) VALUES (:id_usuario, :id_grupo, :id_rol)',
            ['id_usuario' => $acceso->idUsuario, 'id_grupo' => $acceso->idGrupo, 'id_rol' => $acceso->idRol],
        );
    }

    /** Cambia el rol de un usuario que ya tiene acceso. */
    public function actualizar(AccesoGrupo $acceso): void
    {
        $this->ejecutar(
            'UPDATE acceso_grupo SET id_rol = :id_rol WHERE id_usuario = :id_usuario AND id_grupo = :id_grupo',
            ['id_rol' => $acceso->idRol, 'id_usuario' => $acceso->idUsuario, 'id_grupo' => $acceso->idGrupo],
        );
    }

    /** Quita el acceso. */
    public function eliminar(int $idUsuario, int $idGrupo): void
    {
        $this->ejecutar('DELETE FROM acceso_grupo WHERE id_usuario = ? AND id_grupo = ?', [$idUsuario, $idGrupo]);
    }
}
