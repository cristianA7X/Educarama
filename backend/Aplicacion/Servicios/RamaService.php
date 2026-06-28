<?php
// backend/Aplicacion/Servicios/RamaService.php

require_once __DIR__ . '/../../Infraestructura/Datos/RamaRepository.php';
require_once __DIR__ . '/../../Dominio/Model/Rama.php';

class RamaService {
    private RamaRepository $ramaRepo;

    public function __construct(RamaRepository $ramaRepo) {
        $this->ramaRepo = $ramaRepo;
    }

    public function listarTodas(): array {
        return $this->ramaRepo->mostrarTodo();
    }

    public function obtenerPorId(int $id): ?Rama {
        $rama = $this->ramaRepo->mostrarPorId($id);
        if (!$rama) {
            throw new Exception("La rama no existe.");
        }
        return $rama;
    }

    public function crear(string $nombre, int $idGrupo, int $idRamaRef): void {
        if (empty($nombre)) {
            throw new Exception("El nombre de la rama no puede estar vacío.");
        }
        
        $nuevaRama = new Rama(0, $nombre, $idGrupo, $idRamaRef);
        $this->ramaRepo->agregar($nuevaRama);
    }

    public function actualizar(int $id, string $nombre, int $idGrupo, int $idRamaRef): void {
        // Verificamos que exista antes de actualizar
        $this->obtenerPorId($id); 

        $ramaModificada = new Rama($id, $nombre, $idGrupo, $idRamaRef);
        $this->ramaRepo->actualizar($ramaModificada);
    }

    public function eliminar(int $id): void {
        // Verificamos que exista antes de borrar
        $this->obtenerPorId($id);
        $this->ramaRepo->eliminar($id);
    }
}
?>