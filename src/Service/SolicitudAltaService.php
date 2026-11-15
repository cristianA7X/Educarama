<?php // src/Service/SolicitudAltaService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\AccesoGrupo;
use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Grupo;
use Educarama\Model\Rama;
use Educarama\Model\Seccion;
use Educarama\Model\SolicitudAlta;
use Educarama\Repository\AccesoGrupoRepository;
use Educarama\Repository\GrupoRepository;
use Educarama\Repository\RamaRepository;
use Educarama\Repository\RolRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\SolicitudAltaRepository;
use Educarama\Repository\UsuarioRepository;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Vista\SolicitudEnPantalla;
use PDO;

final class SolicitudAltaService extends ServicioBase
{
    private const LARGO_MOTIVO = 16383;

    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly SolicitudAltaRepository $solicitudes,
        private readonly GrupoRepository $grupos,
        private readonly SeccionRepository $secciones,
        private readonly AccesoGrupoRepository $accesosGrupo,
        private readonly RolRepository $roles,
        private readonly RamaRepository $ramas,
        private readonly UsuarioRepository $usuarios
    ) {
        parent::__construct($conexion);
    }

    public function enviar(int $idUsuario, array $datosGrupo, array $datosSecciones): SolicitudAlta
    {
        foreach ($this->solicitudes->listarPorUsuario($idUsuario) as $anterior) {
            if ($anterior->estado === EstadoSolicitud::Pendiente) {
                throw new ReglaNegocioException('Ya tenés una solicitud pendiente: esperá a que la revisen.');
            }
        }
        $grupo = $this->limpiarGrupo($datosGrupo);
        $secciones = $this->limpiarSecciones($datosSecciones);
        $this->validarDatos($grupo, $secciones);
        $id = $this->solicitudes->insertar(new SolicitudAlta(
            id: null,
            idUsuario: $idUsuario,
            datosGrupo: $grupo,
            datosSecciones: $secciones,
            estado: EstadoSolicitud::Pendiente,
            fecha: null,
            idAdminRevisor: null,
        ));
        return $this->solicitudes->buscarPorId($id)
            ?? throw new \LogicException("La solicitud $id no se encontró después de insertarla.");
    }

    public function misSolicitudes(int $idUsuario): array
    {
        return $this->solicitudes->listarPorUsuario($idUsuario);
    }

    public function ramas(): array
    {
        return $this->ramas->listarTodas();
    }

    public function misSolicitudesEnPantalla(int $idUsuario): array
    {
        return array_map(fn (SolicitudAlta $s) => $this->enPantalla($s, false), $this->solicitudes->listarPorUsuario($idUsuario));
    }

    public function detalle(int $idUsuario, int $idSolicitud): SolicitudEnPantalla
    {
        return $this->enPantalla($this->buscar($idUsuario, $idSolicitud), $this->esAdmin($idUsuario));
    }

    public function buscar(int $idUsuario, int $idSolicitud): SolicitudAlta
    {
        $solicitud = $this->solicitudes->buscarPorId($idSolicitud);
        if ($solicitud === null || ($solicitud->idUsuario !== $idUsuario && !$this->esAdmin($idUsuario))) {
            throw new NoEncontradoException('La solicitud no existe.');
        }
        return $solicitud;
    }

    public function listarPendientes(int $idAdmin): array
    {
        $this->permisos->exigirAdmin($idAdmin);
        return $this->solicitudes->listarPorEstado(EstadoSolicitud::Pendiente);
    }

    public function pendientesEnPantalla(int $idAdmin): array
    {
        return array_map(fn (SolicitudAlta $s) => $this->enPantalla($s, true), $this->listarPendientes($idAdmin));
    }

    public function aprobar(int $idAdmin, int $idSolicitud): int
    {
        $this->permisos->exigirAdmin($idAdmin);
        $solicitud = $this->pendiente($idSolicitud);
        $this->validarDatos($solicitud->datosGrupo, $solicitud->datosSecciones);
        $rolJefe = $this->roles->buscarPorNivel(NivelRol::Jefe)
            ?? throw new \LogicException('Falta el rol Jefe en la tabla rol.');
            
        return $this->transaccion(function () use ($solicitud, $idAdmin, $rolJefe): int {
            $idGrupo = $this->grupos->insertar(new Grupo(
                id: null,
                codigo: $solicitud->datosGrupo['codigo'],
                nombre: $solicitud->datosGrupo['nombre'],
            ));
            foreach ($solicitud->datosSecciones as $datos) {
                $this->secciones->insertar(new Seccion(
                    id: null,
                    nombre: $datos['nombre'],
                    idGrupo: $idGrupo,
                    idRama: $datos['id_rama'],
                ));
            }
            $this->accesosGrupo->insertar(new AccesoGrupo($solicitud->idUsuario, $idGrupo, (int) $rolJefe->id));
            $this->solicitudes->registrarRevision((int) $solicitud->id, EstadoSolicitud::Aprobada, $idAdmin);
            return $idGrupo;
        });
    }

    public function rechazar(int $idAdmin, int $idSolicitud, string $motivo): void
    {
        $this->permisos->exigirAdmin($idAdmin);
        $solicitud = $this->pendiente($idSolicitud);
        $motivo = Validacion::textoOpcional($motivo, self::LARGO_MOTIVO, 'El motivo')
            ?? throw new ReglaNegocioException('Para rechazar hay que escribir el motivo.');
        $this->solicitudes->registrarRevision((int) $solicitud->id, EstadoSolicitud::Rechazada, $idAdmin, $motivo);
        $this->notificarRechazo($solicitud, $motivo);
    }

    private function notificarRechazo(SolicitudAlta $solicitud, string $motivo): void
    {
    }

    private function pendiente(int $idSolicitud): SolicitudAlta
    {
        $solicitud = $this->solicitudes->buscarPorId($idSolicitud)
            ?? throw new NoEncontradoException('La solicitud no existe.');
        if ($solicitud->estado !== EstadoSolicitud::Pendiente) {
            throw new ReglaNegocioException('La solicitud ya fue ' . mb_strtolower($solicitud->estado->etiqueta()) . '.');
        }
        return $solicitud;
    }

    private function enPantalla(SolicitudAlta $solicitud, bool $conSolicitante): SolicitudEnPantalla
    {
        $ramas = [];
        foreach ($this->ramas->listarTodas() as $rama) {
            $ramas[$rama->id] = $rama->titulo;
        }
        $secciones = array_map(fn (array $d) => [
            'nombre'  => (string) ($d['nombre'] ?? ''),
            'id_rama' => (int) ($d['id_rama'] ?? 0),
            'rama'    => $ramas[$d['id_rama'] ?? 0] ?? 'Rama desconocida',
        ], $solicitud->datosSecciones);
        $solicitante = $conSolicitante ? $this->usuarios->buscarPorId($solicitud->idUsuario) : null;
        return new SolicitudEnPantalla($solicitud, $solicitante?->nombre, $solicitante?->email, $secciones);
    }

    private function esAdmin(int $idUsuario): bool
    {
        try {
            $this->permisos->exigirAdmin($idUsuario);
            return true;
        } catch (AccesoDenegadoException) {
            return false;
        }
    }

    private function limpiarGrupo(array $datos): array
    {
        return [
            'codigo' => Validacion::textoOpcional(self::texto($datos['codigo'] ?? null), 20, 'El código del grupo') ?? '',
            'nombre' => Validacion::textoOpcional(self::texto($datos['nombre'] ?? null), 100, 'El nombre del grupo') ?? '',
        ];
    }

    private function limpiarSecciones(array $datos): array
    {
        $secciones = [];
        foreach ($datos as $seccion) {
            if (!is_array($seccion)) {
                throw new ReglaNegocioException('Los datos de las secciones no son válidos.');
            }
            $idRama = $seccion['id_rama'] ?? null;
            $secciones[] = [
                'nombre'  => Validacion::textoOpcional(self::texto($seccion['nombre'] ?? null), 100, 'El nombre de la sección') ?? '',
                'id_rama' => is_numeric($idRama) ? (int) $idRama : 0,
            ];
        }
        return $secciones;
    }

    private function validarDatos(array $grupo, array $secciones): void
    {
        if ($grupo['codigo'] === '') {
            throw new ReglaNegocioException('El código del grupo es obligatorio.');
        }
        if ($grupo['nombre'] === '') {
            throw new ReglaNegocioException('El nombre del grupo es obligatorio.');
        }
        if ($this->grupos->buscarPorCodigo($grupo['codigo']) !== null) {
            throw new ReglaNegocioException("Ya hay un grupo registrado con el código {$grupo['codigo']}.");
        }
        if ($secciones === []) {
            throw new ReglaNegocioException('Hay que cargar al menos una sección.');
        }
        $nombres = [];
        foreach ($secciones as $seccion) {
            if ($seccion['nombre'] === '') {
                throw new ReglaNegocioException('Cada sección tiene que tener nombre.');
            }
            if ($this->ramas->buscarPorId($seccion['id_rama']) === null) {
                throw new ReglaNegocioException("La sección {$seccion['nombre']} no tiene una rama válida.");
            }
            $clave = mb_strtolower($seccion['nombre']);
            if (isset($nombres[$clave])) {
                throw new ReglaNegocioException("La sección {$seccion['nombre']} está repetida.");
            }
            $nombres[$clave] = true;
        }
    }

    private static function texto(mixed $valor): ?string
    {
        return is_string($valor) || is_int($valor) ? (string) $valor : null;
    }
}