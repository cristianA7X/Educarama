<?php

require_once __DIR__ . '/../Datos/Database.php';
require_once __DIR__ . '/../Datos/PersonaRepository.php';
require_once __DIR__ . '/../Datos/ProtagonistaRepository.php';
require_once __DIR__ . '/../Datos/RamaRepository.php';
require_once __DIR__ . '/../Datos/PatrullaRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/ProtagonistaService.php';

class ProtagonistaController
{
    private PDO $conexion;
    private ProtagonistaService $servicio;
    private RamaRepository $ramaRepository;
    private PatrullaRepository $patrullaRepository;

    public function __construct()
    {
        $this->conexion = Database::getConexion();

        $personaRepository = new PersonaRepository($this->conexion);
        $protagonistaRepository = new ProtagonistaRepository($this->conexion);

        $this->ramaRepository = new RamaRepository($this->conexion);
        $this->patrullaRepository = new PatrullaRepository($this->conexion);

        $this->servicio = new ProtagonistaService(
            $this->conexion,
            $personaRepository,
            $protagonistaRepository
        );
    }

    public function registrarSiEsPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->servicio->registrarDesdeFormulario($_POST);

            header("Location: protagonistas.php");
            exit;
        }
    }

    public function listarCards(): array
    {
        return $this->servicio->listarCards();
    }

    public function obtenerPerfilDesdeUrl(): ?array
    {
        if (empty($_GET['id'])) {
            return null;
        }

        return $this->servicio->obtenerPerfil((int) $_GET['id']);
    }

    public function listarRamas(): array
    {
        return $this->ramaRepository->listarTodo();
    }

    public function listarPatrullas(): array
    {
        return $this->patrullaRepository->listarTodo();
    }
}
?>
