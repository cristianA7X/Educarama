<?php // src/Repository/SolicitudAltaRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Model\SolicitudAlta;
use PDO;

final class SolicitudAltaRepository extends RepositorioBase
{
    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?SolicitudAlta
    {
        return $this->buscarUno('SELECT * FROM solicitud_alta WHERE id = ?', [$id], SolicitudAlta::class);
    }

    public function listarPorEstado(EstadoSolicitud $estado): array
    {
        return $this->listar(
            'SELECT * FROM solicitud_alta WHERE estado = ? ORDER BY fecha, id',
            [$estado->value],
            SolicitudAlta::class,
        );
    }

    public function listarPorUsuario(int $idUsuario): array
    {
        return $this->listar(
            'SELECT * FROM solicitud_alta WHERE id_usuario = ? ORDER BY fecha DESC, id DESC',
            [$idUsuario],
            SolicitudAlta::class,
        );
    }

    public function insertar(SolicitudAlta $solicitud): int
    {
        $solicitud->id = $this->insertarFila(
            'INSERT INTO solicitud_alta (id_usuario, datos_grupo, datos_secciones, estado)
             VALUES (:id_usuario, :datos_grupo, :datos_secciones, :estado)',
            [
                'id_usuario'      => $solicitud->idUsuario,
                'datos_grupo'     => $this->aJson($solicitud->datosGrupo),
                'datos_secciones' => $this->aJson($solicitud->datosSecciones),
                'estado'          => $solicitud->estado->value,
            ],
        );
        return $solicitud->id;
    }

    public function registrarRevision(
        int $idSolicitud,
        EstadoSolicitud $estado,
        int $idAdminRevisor,
        ?string $motivoRechazo = null,
    ): void {
        $this->ejecutar(
            'UPDATE solicitud_alta SET estado = ?, id_admin_revisor = ?, motivo_rechazo = ? WHERE id = ?',
            [$estado->value, $idAdminRevisor, $motivoRechazo, $idSolicitud],
        );
    }

    private function aJson(array $datos): string
    {
        return json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}