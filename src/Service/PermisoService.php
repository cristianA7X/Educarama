<?php // src/Service/PermisoService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Repository\AccesoGrupoRepository;
use Educarama\Repository\AccesoSeccionRepository;
use Educarama\Repository\RolRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\UsuarioRepository;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use PDO;

final class PermisoService extends ServicioBase
{
    public function __construct(
        PDO $conexion,
        private readonly AccesoGrupoRepository $accesosGrupo,
        private readonly AccesoSeccionRepository $accesosSeccion,
        private readonly RolRepository $roles,
        private readonly SeccionRepository $secciones,
        private readonly UsuarioRepository $usuarios
    ) {
        parent::__construct($conexion);
    }

    public function nivelEnGrupo(int $idUsuario, int $idGrupo): ?NivelRol
    {
        $acceso = $this->accesosGrupo->buscar($idUsuario, $idGrupo);
        return $acceso === null ? null : $this->nivelDeRol($acceso->idRol);
    }

    public function esJefeDeGrupo(int $idUsuario, int $idGrupo): bool
    {
        return $this->nivelEnGrupo($idUsuario, $idGrupo) === NivelRol::Jefe;
    }

    public function tieneAccesoAlGrupo(int $idUsuario, int $idGrupo): bool
    {
        if ($this->nivelEnGrupo($idUsuario, $idGrupo) !== null) {
            return true;
        }
        foreach ($this->secciones->listarPorGrupo($idGrupo) as $seccion) {
            if ($this->nivelEnSeccion($idUsuario, (int) $seccion->id) !== null) {
                return true;
            }
        }
        return false;
    }

    public function puedeEnGrupo(int $idUsuario, int $idGrupo, NivelRol $minimo): bool
    {
        return $this->alcanza($this->nivelEnGrupo($idUsuario, $idGrupo), $minimo);
    }

    public function exigirEnGrupo(int $idUsuario, int $idGrupo, NivelRol $minimo): NivelRol
    {
        $nivel = $this->nivelEnGrupo($idUsuario, $idGrupo);
        if (!$this->alcanza($nivel, $minimo)) {
            throw new AccesoDenegadoException(
                'Necesitás el rol ' . $minimo->etiqueta() . ' en este grupo para hacer esto.'
            );
        }
        return $nivel;
    }

    public function nivelEnSeccion(int $idUsuario, int $idSeccion): ?NivelRol
    {
        $seccion = $this->secciones->buscarPorId($idSeccion)
            ?? throw new NoEncontradoException('La sección no existe.');
        if ($this->esJefeDeGrupo($idUsuario, $seccion->idGrupo)) {
            return NivelRol::Jefe;
        }
        $acceso = $this->accesosSeccion->buscar($idUsuario, $idSeccion);
        return $acceso === null ? null : $this->nivelDeRol($acceso->idRol);
    }

    public function puedeEnSeccion(int $idUsuario, int $idSeccion, NivelRol $minimo): bool
    {
        return $this->alcanza($this->nivelEnSeccion($idUsuario, $idSeccion), $minimo);
    }

    public function exigirEnSeccion(int $idUsuario, int $idSeccion, NivelRol $minimo): NivelRol
    {
        $nivel = $this->nivelEnSeccion($idUsuario, $idSeccion);
        if (!$this->alcanza($nivel, $minimo)) {
            throw new AccesoDenegadoException(
                'Necesitás el rol ' . $minimo->etiqueta() . ' en esta sección para hacer esto.'
            );
        }
        return $nivel;
    }

    public function exigirVisibleEnSeccion(int $idUsuario, int $idSeccion, NivelRol $minimo, string $mensaje404): NivelRol
    {
        $nivel = $this->nivelEnSeccion($idUsuario, $idSeccion)
            ?? throw new NoEncontradoException($mensaje404);
        if (!$this->alcanza($nivel, $minimo)) {
            throw new AccesoDenegadoException(
                'Necesitás el rol ' . $minimo->etiqueta() . ' en esta sección para hacer esto.'
            );
        }
        return $nivel;
    }

    public function exigirAdmin(int $idUsuario): void
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);
        if ($usuario === null || !$usuario->esAdmin) {
            throw new AccesoDenegadoException('Solo un administrador puede hacer esto.');
        }
    }

    private function nivelDeRol(int $idRol): NivelRol
    {
        $rol = $this->roles->buscarPorId($idRol)
            ?? throw new \LogicException("El rol $idRol no existe en la tabla rol.");
        return NivelRol::from($rol->nivel);
    }

    private function alcanza(?NivelRol $nivel, NivelRol $minimo): bool
    {
        return $nivel !== null && $nivel->value >= $minimo->value;
    }
}