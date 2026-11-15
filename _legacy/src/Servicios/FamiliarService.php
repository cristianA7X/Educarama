<?php

require_once __DIR__ . '/src/Datos/FamiliarRepository.php';
require_once __DIR__ . '/src/Datos/PersonaRepository.php';
require_once __DIR__ . '/src/Model/Familiar.php';
require_once __DIR__ . '/src/Model/Persona.php';

class FamiliarService 
{
    private FamiliarRepository $familiarRepo;
    private PersonaRepository $personaRepo;

    public function __construct(FamiliarRepository $familiarRepo, PersonaRepository $personaRepo) 
    {
        $this->familiarRepo = $familiarRepo;
        $this->personaRepo = $personaRepo;
    }

    public function listarTodos(): array 
    {
        return $this->familiarRepo->mostrarTodo();
    }

    public function obtenerPorId(int $id): ?Familiar 
    {
        $familiar = $this->familiarRepo->mostrarPorId($id);
        if (!$familiar) {
            throw new Exception("El familiar no existe.");
        }
        return $familiar;
    }

    // El front manda los datos de la persona y del familiar juntos
    public function crear(array $datos): void 
    {
        if (empty($datos['dni']) || empty($datos['nombre'])) {
            throw new Exception("Faltan datos obligatorios (DNI o Nombre).");
        }
        
        // 1. Creamos la Persona primero
        $nuevaPersona = new Persona(0, (int)$datos['dni'], $datos['nombre'], $datos['apellido'], $datos['fecha_nacimiento']);
        $idPersona = $this->personaRepo->agregar($nuevaPersona);

        // 2. Creamos el Familiar usando el ID de la persona que acabamos de crear
        $nuevoFamiliar = new Familiar(0, (int)$datos['id_protagonista'], (int)$idPersona, (string)$datos['parentesco']);
        $this->familiarRepo->agregar($nuevoFamiliar);
    }

    public function eliminar(int $id): void 
    {
        $familiar = $this->obtenerPorId($id);
        $this->familiarRepo->eliminar($familiar->getId());
    }
}