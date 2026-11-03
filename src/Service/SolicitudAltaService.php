<?php
// src/Service/SolicitudAltaService.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\AccesoGrupo;
use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Model\Enum\NivelRol;
use Educarama\Model\Grupo;
use Educarama\Model\Seccion;
use Educarama\Model\SolicitudAlta;
use Educarama\Repository\AccesoGrupoRepository;
use Educarama\Repository\GrupoRepository;
use Educarama\Repository\RamaRepository;
use Educarama\Repository\RolRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\SolicitudAltaRepository;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;

/**
 * Alta de un grupo nuevo (CLAUDE.md, "Reglas de negocio"):
 *
 *   el usuario se registra sin accesos → envía una solicitud → un admin la
 *   aprueba → se crean el grupo y sus secciones, y el usuario queda como Jefe del grupo.
 *
 * Contenido de la solicitud (se guarda como JSON):
 *   datosGrupo     = ['codigo' => '64', 'nombre' => 'San Martín']                 (columnas de `grupo`)
 *   datosSecciones = [['nombre' => 'Manada Seeonee', 'id_rama' => 1], ...]        (columnas de `seccion`)
 *
 * Reglas:
 *  - Se validan al enviar y otra vez al aprobar (el código pudo haberse usado
 *    mientras tanto): código no usado por otro grupo, al menos una sección,
 *    ramas válidas y sin nombres de sección repetidos.
 *  - Una sola solicitud pendiente por usuario. Si se la rechazan, puede enviar otra.
 *  - No se edita: si tiene un error, el admin la rechaza (con motivo) y el usuario la reenvía.
 *  - Aprobar y rechazar: solo admin. Rechazar exige motivo, que ve el solicitante.
 *  - La validación con Cruz del Sur queda para más adelante.
 */
final class SolicitudAltaService extends ServicioBase
{
    /** Lo que entra en la columna `motivo_rechazo` (TEXT, utf8mb4). No es una regla de negocio. */
    private const LARGO_MOTIVO = 16383;

    private PermisoService $permisos;
    private SolicitudAltaRepository $solicitudes;
    private GrupoRepository $grupos;
    private SeccionRepository $secciones;
    private AccesoGrupoRepository $accesosGrupo;
    private RolRepository $roles;
    private RamaRepository $ramas;

    public function __construct(
        ?PermisoService $permisos = null,
        ?SolicitudAltaRepository $solicitudes = null,
        ?GrupoRepository $grupos = null,
        ?SeccionRepository $secciones = null,
        ?AccesoGrupoRepository $accesosGrupo = null,
        ?RolRepository $roles = null,
        ?RamaRepository $ramas = null,
    ) {
        parent::__construct();
        $this->permisos = $permisos ?? new PermisoService();
        $this->solicitudes = $solicitudes ?? new SolicitudAltaRepository();
        $this->grupos = $grupos ?? new GrupoRepository();
        $this->secciones = $secciones ?? new SeccionRepository();
        $this->accesosGrupo = $accesosGrupo ?? new AccesoGrupoRepository();
        $this->roles = $roles ?? new RolRepository();
        $this->ramas = $ramas ?? new RamaRepository();
    }

    // -----------------------------------------------------------------
    // Solicitante
    // -----------------------------------------------------------------

    /**
     * Envía una solicitud. Los datos llegan del formulario (ver formato arriba);
     * se guardan limpios: sin espacios de más y solo con las claves esperadas.
     *
     * @param array<string, mixed>             $datosGrupo
     * @param array<int, array<string, mixed>> $datosSecciones
     * @throws ReglaNegocioException
     */
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

    /** @return SolicitudAlta[] Las del usuario, de la más nueva a la más vieja (con su motivo si se rechazó). */
    public function misSolicitudes(int $idUsuario): array
    {
        return $this->solicitudes->listarPorUsuario($idUsuario);
    }

    /**
     * Una solicitud, para su dueño o para un admin. A cualquier otro le
     * responde que no existe (no confirma que hay una solicitud con ese id).
     */
    public function buscar(int $idUsuario, int $idSolicitud): SolicitudAlta
    {
        $solicitud = $this->solicitudes->buscarPorId($idSolicitud);

        if ($solicitud === null || ($solicitud->idUsuario !== $idUsuario && !$this->esAdmin($idUsuario))) {
            throw new NoEncontradoException('La solicitud no existe.');
        }

        return $solicitud;
    }

    // -----------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------

    /** @return SolicitudAlta[] Las pendientes, de la más vieja a la más nueva. */
    public function listarPendientes(int $idAdmin): array
    {
        $this->permisos->exigirAdmin($idAdmin);

        return $this->solicitudes->listarPorEstado(EstadoSolicitud::Pendiente);
    }

    /**
     * Aprueba la solicitud: en una sola transacción crea el grupo, sus secciones
     * y el acceso del solicitante como Jefe del grupo (que lo hace Jefe de todas
     * las secciones por derivación). Devuelve el id del grupo nuevo.
     *
     * @throws ReglaNegocioException si ya fue revisada o sus datos ya no son válidos
     */
    public function aprobar(int $idAdmin, int $idSolicitud): int
    {
        $this->permisos->exigirAdmin($idAdmin);
        $solicitud = $this->pendiente($idSolicitud);

        // Se vuelve a validar: entre el envío y la aprobación otro grupo pudo tomar el código.
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

    /**
     * Rechaza la solicitud. El motivo es obligatorio: lo ve el solicitante para
     * corregir y reenviar.
     *
     * @throws ReglaNegocioException
     */
    public function rechazar(int $idAdmin, int $idSolicitud, string $motivo): void
    {
        $this->permisos->exigirAdmin($idAdmin);
        $solicitud = $this->pendiente($idSolicitud);

        $motivo = Validacion::textoOpcional($motivo, self::LARGO_MOTIVO, 'El motivo')
            ?? throw new ReglaNegocioException('Para rechazar hay que escribir el motivo.');

        $this->solicitudes->registrarRevision((int) $solicitud->id, EstadoSolicitud::Rechazada, $idAdmin, $motivo);

        $this->notificarRechazo($solicitud, $motivo);
    }

    // -----------------------------------------------------------------
    // Auxiliares
    // -----------------------------------------------------------------

    /**
     * Avisarle al solicitante por email que su solicitud se rechazó y por qué.
     *
     * Para más adelante: todavía no hay envío de emails. Por ahora no hace nada;
     * el solicitante ve el motivo en el estado de su solicitud (misSolicitudes).
     */
    private function notificarRechazo(SolicitudAlta $solicitud, string $motivo): void
    {
        // Sin implementar: acá va el envío del email.
    }

    /** La solicitud, si existe y todavía no fue revisada. */
    private function pendiente(int $idSolicitud): SolicitudAlta
    {
        $solicitud = $this->solicitudes->buscarPorId($idSolicitud)
            ?? throw new NoEncontradoException('La solicitud no existe.');

        if ($solicitud->estado !== EstadoSolicitud::Pendiente) {
            throw new ReglaNegocioException('La solicitud ya fue ' . mb_strtolower($solicitud->estado->etiqueta()) . '.');
        }

        return $solicitud;
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

    /**
     * Deja solo las claves esperadas, como texto sin espacios de más.
     *
     * @return array{codigo: string, nombre: string}
     */
    private function limpiarGrupo(array $datos): array
    {
        return [
            'codigo' => Validacion::textoOpcional(self::texto($datos['codigo'] ?? null), 20, 'El código del grupo') ?? '',
            'nombre' => Validacion::textoOpcional(self::texto($datos['nombre'] ?? null), 100, 'El nombre del grupo') ?? '',
        ];
    }

    /** @return list<array{nombre: string, id_rama: int}> */
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

    /**
     * Reglas del contenido. Se usa al enviar y al aprobar.
     *
     * @param array{codigo: string, nombre: string}         $grupo
     * @param list<array{nombre: string, id_rama: int}>    $secciones
     */
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

    /** Un valor del formulario como texto (null si no es texto). */
    private static function texto(mixed $valor): ?string
    {
        return is_string($valor) || is_int($valor) ? (string) $valor : null;
    }
}
