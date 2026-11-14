<?php

require_once __DIR__ . '/src/Dominio/Repositorio/MostrarPorIdInterface.php';

class ObtenerPerfilProtagonistaService {
    
    private MostrarPorIdInterface $protagonistaRepo;

    public function __construct(MostrarPorIdInterface $protagonistaRepo) {
        $this->protagonistaRepo = $protagonistaRepo;
    }

    public function ejecutar(int $idProtagonista) {
        // Buscamos al protagonista
        $protagonista = $this->protagonistaRepo->mostrarPorId($idProtagonista);
        
        if (!$protagonista) {
            throw new Exception("El protagonista no existe en el sistema.");
        }

        return $protagonista;
    }
}
?>