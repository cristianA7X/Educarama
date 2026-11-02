<?php
// src/Repository/SolicitudAltaRepository.php

declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Model\SolicitudAlta;

/**
 * Lectura y escritura de `solicitud_alta`: el pedido de un usuario para dar de
 * alta su grupo y sus secciones, que revisa un dev (usuario.es_admin).
 *
 * Una vez enviada, la solicitud no se edita: solo se registra su revisión.
 * Crear el grupo, las secciones y el acceso de Jefe al aprobarla lo hace el Service.
 * No tiene eliminar: queda como historial.
 */
final class SolicitudAltaRepository extends RepositorioBase
{
    public function buscarPorId(int $id): ?SolicitudAlta
    {
        return $this->buscarUno('SELECT * FROM solicitud_alta WHERE id = ?', [$id], SolicitudAlta::class);
    }

    /**
     * Ej.: listarPorEstado(EstadoSolicitud::Pendiente) para el panel del dev.
     *
     * @return SolicitudAlta[] De la más vieja a la más nueva (primero las que esperan hace más tiempo).
     */
    public function listarPorEstado(EstadoSolicitud $estado): array
    {
        return $this->listar(
            'SELECT * FROM solicitud_alta WHERE estado = ? ORDER BY fecha, id',
            [$estado->value],
            SolicitudAlta::class,
        );
    }

    /** @return SolicitudAlta[] Las solicitudes de un usuario, de la más nueva a la más vieja. */
    public function listarPorUsuario(int $idUsuario): array
    {
        return $this->listar(
            'SELECT * FROM solicitud_alta WHERE id_usuario = ? ORDER BY fecha DESC, id DESC',
            [$idUsuario],
            SolicitudAlta::class,
        );
    }

    /**
     * Guarda una solicitud nueva, le completa el id y lo devuelve.
     * Los arrays se guardan como texto JSON; la fecha la pone la base
     * (en el objeto sigue en null hasta volver a leerlo).
     */
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

    /** Registra que un dev aprobó o rechazó la solicitud. */
    public function registrarRevision(int $idSolicitud, EstadoSolicitud $estado, int $idAdminRevisor): void
    {
        $this->ejecutar(
            'UPDATE solicitud_alta SET estado = ?, id_admin_revisor = ? WHERE id = ?',
            [$estado->value, $idAdminRevisor, $idSolicitud],
        );
    }

    /**
     * Array de PHP → texto JSON (lo inverso del json_decode de SolicitudAlta::desdeFila).
     * JSON_UNESCAPED_UNICODE deja los acentos legibles en la base ('Sección' y no 'Sección').
     */
    private function aJson(array $datos): string
    {
        return json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
