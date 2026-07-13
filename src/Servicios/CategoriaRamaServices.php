<?php

require_once __DIR__ . '/../../Infraestructura/Datos/CategoriaRamaRepository.php';
require_once __DIR__ . '/../../Dominio/Model/CategoriaRama.php';

class CategoriaRamaService {
    private CategoriaRamaRepository $categoriaRepo;

    public function __construct(CategoriaRamaRepository $categoriaRepo) {
        $this->categoriaRepo = $categoriaRepo;
    }

    public function listarTodas(): array {
        return $this->categoriaRepo->mostrarTodo();
    }

    public function obtenerPorId(int $id): ?CategoriaRama {
        $categoria = $this->categoriaRepo->mostrarPorId($id);
        if (!$categoria) {
            throw new Exception("La categoría de rama no existe.");
        }
        return $categoria;
    }

    public function crear(string $nombre): void {
        if (empty(trim($nombre))) {
            throw new Exception("El nombre de la categoría no puede estar vacío.");
        }
        
        $nuevaCategoria = new CategoriaRama(0, $nombre);
        $this->categoriaRepo->agregar($nuevaCategoria);
    }

    public function actualizar(int $id, string $nombre): void {
        if (empty(trim($nombre))) {
            throw new Exception("El nombre de la categoría no puede estar vacío.");
        }

        // Verificamos que exista antes de intentar actualizar
        $this->obtenerPorId($id); 

        $categoriaModificada = new CategoriaRama($id, $nombre);
        $this->categoriaRepo->actualizar($categoriaModificada);
    }

    public function eliminar(int $id): void {
        // Verificamos que exista
        $this->obtenerPorId($id);
        
        // Acá se podría agregar una validación de negocio: 
        // "No se puede borrar si hay Ramas o Competencias asociadas a esta categoría"
        // Por ahora lo hacemos directo.
        $this->categoriaRepo->eliminar($id);
    }
}
?>