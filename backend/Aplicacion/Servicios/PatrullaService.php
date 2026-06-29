<?php

require_once __DIR__ . '/../../Infraestructura/Datos/PatrullaRepository.php';
require_once __DIR__ . '/../../Dominio/Model/Patrulla.php';

class PatrullaService
{
    private PatrullaRepository $patrullaRepository;

    public function __construct(PatrullaRepository $patrullaRepository)
    {
        $this->patrullaRepository = $patrullaRepository;
    }

    public function listarTodos(): array
    {
        return $this->patrullaRepository->mostrarTodo();
    }   

    public function obtenerPorId(int $id): ?Patrulla
    {
        $patrulla = $this->patrullaRepository->mostrarPorId($id);
        if (!$patrulla) {
            throw new Exception("La patrulla no existe.");
        }
        return $patrulla;
    }

    public function crear(array $datos): void
    {
        if (empty($datos['nombre']) || empty($datos['id_rama'])) {
            throw new Exception("Faltan datos obligatorios (Nombre o Rama).");
        }

        $nuevaPatrulla = new Patrulla(0, $datos['nombre'], (int)$datos['id_rama']);
        $this->patrullaRepository->agregar($nuevaPatrulla);
    }

    public function eliminar(int $id): void
    {
        $patrulla = $this->obtenerPorId($id);
        $this->patrullaRepository->eliminar($patrulla);
    }

    public function actualizar(int $id, array $datos): void
    {
        $patrulla = $this->obtenerPorId($id);

        if (isset($datos['nombre'])) {
            $patrulla->nombre = $datos['nombre'];
        }
        if (isset($datos['id_rama'])) {
            $patrulla->id_rama = (int)$datos['id_rama'];
        }

        $this->patrullaRepository->actualizar($patrulla);
    }
}