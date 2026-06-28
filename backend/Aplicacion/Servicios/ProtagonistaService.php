<?php
// backend/Aplicacion/Servicios/ProtagonistaService.php

require_once __DIR__ . '/../../Infraestructura/Datos/ProtagonistaRepository.php';
require_once __DIR__ . '/../../Infraestructura/Datos/PersonaRepository.php';
require_once __DIR__ . '/../../Dominio/Model/Protagonista.php';
require_once __DIR__ . '/../../Dominio/Model/Persona.php';

class ProtagonistaService {
    private ProtagonistaRepository $protagonistaRepo;
    private PersonaRepository $personaRepo;

    public function __construct(ProtagonistaRepository $protagonistaRepo, PersonaRepository $personaRepo) {
        $this->protagonistaRepo = $protagonistaRepo;
        $this->personaRepo = $personaRepo;
    }

    public function listarTodos(): array {
        return $this->protagonistaRepo->mostrarTodo();
    }

    public function obtenerPorId(int $id): ?Protagonista {
        $protagonista = $this->protagonistaRepo->mostrarPorId($id);
        if (!$protagonista) {
            throw new Exception("El protagonista no existe.");
        }
        return $protagonista;
    }

    // El front manda los datos de la persona y del scout juntos
    public function crear(array $datos): void {
        if (empty($datos['dni']) || empty($datos['nombre']) || empty($datos['id_rama'])) {
            throw new Exception("Faltan datos obligatorios (DNI, Nombre o Rama).");
        }
        
        // 1. Creamos la Persona primero
        $nuevaPersona = new Persona(0, (int)$datos['dni'], $datos['nombre'], $datos['apellido'], $datos['fecha_nacimiento']);
        $idPersona = $this->personaRepo->agregar($nuevaPersona);

        // 2. Creamos el Protagonista usando el ID de la persona que acabamos de crear
        $promesa = isset($datos['promesa']) ? (bool)$datos['promesa'] : false;
        $idPatrulla = isset($datos['id_patrulla']) ? (int)$datos['id_patrulla'] : null;
        
        $nuevoProtagonista = new Protagonista(0, $promesa, $idPersona, (int)$datos['id_rama'], $idPatrulla, null);
        $this->protagonistaRepo->agregar($nuevoProtagonista);
    }

    // Método específico que pediste: Asignar a una rama
    public function asignarRama(int $idProtagonista, int $idRama): void {
        $protagonista = $this->obtenerPorId($idProtagonista); // Verifica que exista
        
        // Modificamos solo el id_rama
        $protagonista->id_rama = $idRama;
        
        // Actualizamos en la base de datos
        $this->protagonistaRepo->actualizar($protagonista);
    }

    public function eliminar(int $id): void {
        $protagonista = $this->obtenerPorId($id);
        
        // Nota: Al borrar el protagonista, deberías decidir si también borrás a la Persona
        // Por ahora, solo borramos el rol de protagonista.
        $this->protagonistaRepo->eliminar($id);
    }
}
?>