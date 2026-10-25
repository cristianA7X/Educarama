<?php

class PatrullaService
{
    private PatrullaRepository $repositorio;

    public function __construct(PatrullaRepository $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    public function listarPatrullas(): array
    {
        return $this->repositorio->listarTodo();
    }

    public function obtenerPorId(int $id): ?array
    {
        return $this->repositorio->obtenerPorId($id);
    }

    public function obtenerIntegrantes(int $idPatrulla): array
    {
        return $this->repositorio->obtenerIntegrantes($idPatrulla);
    }

    public function crearDesdeFormulario(array $post): void
    {
        $nombre = trim($post['nombre'] ?? '');
        $idRama = $post['id_rama'] ?? null;

        if ($nombre === '') {
            throw new Exception("El nombre de la patrulla es obligatorio.");
        }

        if (empty($idRama)) {
            throw new Exception("Debe seleccionar una rama.");
        }

        $this->repositorio->crear([
            'nombre' => $nombre,
            'id_rama' => $idRama
        ]);
    }
}