
<?php

require_once __DIR__ . '/../Datos/Database.php';
require_once __DIR__ . '/../Datos/PatrullaRepository.php';
require_once __DIR__ . '/../Datos/RamaRepository.php';
require_once __DIR__ . '/../../Aplicacion/Servicios/PatrullaService.php';

class PatrullaController
{
    private PatrullaService $servicio;
    private RamaRepository $ramaRepository;

    public function __construct()
    {
        $conexion = Database::getConexion();

        $repositorio = new PatrullaRepository($conexion);
        $this->ramaRepository = new RamaRepository($conexion);

        $this->servicio = new PatrullaService($repositorio);
    }

    public function listar(): array
    {
        return $this->servicio->listarPatrullas();
    }

    public function crearSiEsPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->servicio->crearDesdeFormulario($_POST);

            header("Location: patrullas.php");
            exit;
        }
    }

    public function listarRamas(): array
    {
        return $this->ramaRepository->listarTodo();
    }
}