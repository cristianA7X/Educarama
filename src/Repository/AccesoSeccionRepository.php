<?php
// src/Repository/AccesoSeccionRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\AccesoSeccion;
use Educarama\Repository\Vista\UsuarioConAcceso;

/**
 * Lectura y escritura de `acceso_seccion`: qué rol tiene un usuario sobre una sección.
 * No tiene id propio: cada fila se identifica por id_usuario + id_seccion.
 *
 * ⚠️ El Jefe de grupo NO tiene filas acá: es Jefe de todas las secciones de su
 * grupo por derivación, y eso lo resuelve el Service mirando acceso_grupo.
 * Por eso listarPorSeccion() no devuelve a los Jefes de grupo.
 */
final class AccesoSeccionRepository extends RepositorioBase
{
    public function buscar(int $idUsuario, int $idSeccion): ?AccesoSeccion
    {
        return $this->buscarUno(
            'SELECT * FROM acceso_seccion WHERE id_usuario = ? AND id_seccion = ?',
            [$idUsuario, $idSeccion],
            AccesoSeccion::class,
        );
    }

    /** @return AccesoSeccion[] Las secciones a las que accede un usuario (sin las derivadas de ser Jefe de grupo). */
    public function listarPorUsuario(int $idUsuario): array
    {
        return $this->listar(
            'SELECT * FROM acceso_seccion WHERE id_usuario = ? ORDER BY id_seccion',
            [$idUsuario],
            AccesoSeccion::class,
        );
    }

    /** @return AccesoSeccion[] Los usuarios asignados a una sección (sin los Jefes de grupo). */
    public function listarPorSeccion(int $idSeccion): array
    {
        return $this->listar(
            'SELECT * FROM acceso_seccion WHERE id_seccion = ? ORDER BY id_usuario',
            [$idSeccion],
            AccesoSeccion::class,
        );
    }

    /**
     * Los usuarios asignados a la sección, con nombre, email y rol, para la
     * pantalla de gestión. Primero los de mayor rol, después por nombre.
     * No incluye a los Jefes de grupo (no tienen filas acá).
     *
     * @return UsuarioConAcceso[]
     */
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

    /** Asigna un usuario a la sección. Si ya estaba asignado, la base lanza una excepción (PK duplicada). */
    public function insertar(AccesoSeccion $acceso): void
    {
        $this->ejecutar(
            'INSERT INTO acceso_seccion (id_usuario, id_seccion, id_rol) VALUES (:id_usuario, :id_seccion, :id_rol)',
            ['id_usuario' => $acceso->idUsuario, 'id_seccion' => $acceso->idSeccion, 'id_rol' => $acceso->idRol],
        );
    }

    /** Cambia el rol de un usuario ya asignado (lo único que puede hacer el Jefe de sección). */
    public function actualizar(AccesoSeccion $acceso): void
    {
        $this->ejecutar(
            'UPDATE acceso_seccion SET id_rol = :id_rol WHERE id_usuario = :id_usuario AND id_seccion = :id_seccion',
            ['id_rol' => $acceso->idRol, 'id_usuario' => $acceso->idUsuario, 'id_seccion' => $acceso->idSeccion],
        );
    }

    /** Quita el acceso. */
    public function eliminar(int $idUsuario, int $idSeccion): void
    {
        $this->ejecutar(
            'DELETE FROM acceso_seccion WHERE id_usuario = ? AND id_seccion = ?',
            [$idUsuario, $idSeccion],
        );
    }
}
