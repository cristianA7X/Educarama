<?php
class ProtagonistaService
{
    private PDO $conexion;
    private PersonaRepository $personaRepository;
    private ProtagonistaRepository $protagonistaRepository;

    public function __construct(
        PDO $conexion,
        PersonaRepository $personaRepository,
        ProtagonistaRepository $protagonistaRepository
    ) {
        $this->conexion = $conexion;
        $this->personaRepository = $personaRepository;
        $this->protagonistaRepository = $protagonistaRepository;
    }

    public function registrarDesdeFormulario(array $post): void
    {
        $apellido = trim($post['apellido'] ?? '');
        $nombre = trim($post['nombre'] ?? '');
        $fechaNacimiento = $post['fecha_nacimiento'] ?? '';
        $dni = trim($post['dni'] ?? '');
        $anioIngreso = !empty($post['anio_ingreso']) ? $post['anio_ingreso'] : null;
        $idRama = $post['id_rama'] ?? null;
        $idPatrulla = !empty($post['id_patrulla']) ? $post['id_patrulla'] : null;
        $promesa = isset($post['promesa']) ? 1 : 0;

        if ($apellido === '') {
            throw new Exception("El apellido es obligatorio.");
        }

        if ($nombre === '') {
            throw new Exception("El nombre es obligatorio.");
        }

        if ($fechaNacimiento === '') {
            throw new Exception("La fecha de nacimiento es obligatoria.");
        }

        if ($dni === '') {
            throw new Exception("El DNI es obligatorio.");
        }

        if (empty($idRama)) {
            throw new Exception("Debe seleccionar una rama.");
        }

        try {
            $this->conexion->beginTransaction();

            $idPersona = $this->personaRepository->crear([
                'apellido' => $apellido,
                'nombre' => $nombre,
                'fecha_nacimiento' => $fechaNacimiento,
                'dni' => $dni
            ]);

            $this->protagonistaRepository->crear([
                'promesa' => $promesa,
                'anio_ingreso' => $anioIngreso,
                'id_persona' => $idPersona,
                'id_rama' => $idRama,
                'id_patrulla' => $idPatrulla
            ]);

            $this->conexion->commit();

        } catch (Exception $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    public function listarCards(): array
    {
        return $this->protagonistaRepository->listarCards();
    }

    public function obtenerPerfil(int $id): ?array
    {
        return $this->protagonistaRepository->obtenerPerfilPorId($id);
    }
}
?>