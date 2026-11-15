<?php // src/Service/FamiliarService.php
declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Enum\NivelRol;
use Educarama\Model\EsFamilia;
use Educarama\Model\Familiar;
use Educarama\Model\Persona;
use Educarama\Model\Protagonista;
use Educarama\Repository\EsFamiliaRepository;
use Educarama\Repository\FamiliarRepository;
use Educarama\Repository\PersonaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\Vista\FichaFamiliar;
use Educarama\Service\Excepcion\NoEncontradoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\Excepcion\RequisitosPendientesException;
use Educarama\Service\Vista\Aviso;
use Educarama\Service\Vista\TipoAviso;
use PDO;

final class FamiliarService extends ServicioBase
{
    public function __construct(
        PDO $conexion,
        private readonly PermisoService $permisos,
        private readonly ProtagonistaRepository $protagonistas,
        private readonly PersonaRepository $personas,
        private readonly FamiliarRepository $familiares,
        private readonly EsFamiliaRepository $vinculos
    ) {
        parent::__construct($conexion);
    }

    public function listarPorProtagonista(int $idUsuario, int $idProtagonista): array
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Ayudante);
        return $this->familiares->listarFichasPorProtagonista($idProtagonista);
    }

    public function buscarFicha(int $idUsuario, int $idProtagonista, int $idFamiliar): FichaFamiliar
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Ayudante);
        return $this->familiares->buscarFicha($idProtagonista, $idFamiliar)
            ?? throw new NoEncontradoException('El familiar no está vinculado a este protagonista.');
    }

    public function vinculadoAOtros(int $idUsuario, int $idProtagonista, int $idFamiliar): bool
    {
        $this->buscarFicha($idUsuario, $idProtagonista, $idFamiliar);
        return count($this->vinculos->listarPorFamiliar($idFamiliar)) > 1;
    }

    public function alta(
        int $idUsuario,
        int $idProtagonista,
        Persona $persona,
        Familiar $familiar,
        EsFamilia $vinculo,
        bool $confirmarIgual = false,
    ): int {
        $protagonista = $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);
        Validacion::persona($persona);
        $this->validarContacto($familiar);
        $this->validarVinculo($vinculo);
        
        $existente = $this->personas->buscarPorDni($persona->dni);
        
        if ($existente === null) {
            return $this->transaccion(function () use ($persona, $familiar, $vinculo, $idProtagonista): int {
                $familiar->idPersona = $this->personas->insertar($persona);
                $idFamiliar = $this->familiares->insertar($familiar);
                $this->vincular($idProtagonista, $idFamiliar, $vinculo);
                return $idFamiliar;
            });
        }
        
        if ($existente->id === $protagonista->idPersona) {
            throw new ReglaNegocioException('Ese DNI es el del propio protagonista.');
        }
        
        $familiarExistente = $this->familiares->buscarPorPersona((int) $existente->id);
        if ($familiarExistente !== null && $this->vinculos->buscar($idProtagonista, (int) $familiarExistente->id) !== null) {
            throw new ReglaNegocioException("{$existente->nombre} {$existente->apellido} ya es familiar de este protagonista.");
        }
        
        if (!$confirmarIgual) {
            throw new RequisitosPendientesException([new Aviso(
                TipoAviso::PersonaExistente,
                "{$existente->nombre} {$existente->apellido} ya está cargada con ese DNI. ¿Vincularla a este protagonista? Se van a conservar sus datos guardados.",
            )]);
        }
        
        return $this->transaccion(function () use ($existente, $familiarExistente, $familiar, $vinculo, $idProtagonista): int {
            $idFamiliar = $familiarExistente?->id;
            if ($idFamiliar === null) {
                $familiar->idPersona = (int) $existente->id;
                $idFamiliar = $this->familiares->insertar($familiar);
            }
            $this->vincular($idProtagonista, $idFamiliar, $vinculo);
            return $idFamiliar;
        });
    }

    public function actualizar(
        int $idUsuario,
        int $idProtagonista,
        Persona $persona,
        Familiar $familiar,
        EsFamilia $vinculo,
    ): void {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);
        $idFamiliar = $familiar->id
            ?? throw new \InvalidArgumentException('Para actualizar hace falta el id del familiar.');
        $guardada = $this->familiares->buscarFicha($idProtagonista, $idFamiliar)
            ?? throw new NoEncontradoException('El familiar no está vinculado a este protagonista.');
            
        $familiar->idPersona = $guardada->familiar->idPersona;
        $persona->id = $guardada->familiar->idPersona;
        $vinculo->idProtagonista = $idProtagonista;
        $vinculo->idFamiliar = $idFamiliar;
        
        Validacion::persona($persona);
        $this->validarContacto($familiar);
        $this->validarVinculo($vinculo);
        
        $conEseDni = $this->personas->buscarPorDni($persona->dni);
        if ($conEseDni !== null && $conEseDni->id !== $persona->id) {
            throw new ReglaNegocioException('Ya existe otra persona con ese DNI.');
        }
        
        $this->transaccion(function () use ($persona, $familiar, $vinculo): void {
            $this->personas->actualizar($persona);
            $this->familiares->actualizar($familiar);
            $this->vinculos->actualizar($vinculo);
        });
    }

    public function desvincular(int $idUsuario, int $idProtagonista, int $idFamiliar, bool $confirmarIgual = false): bool
    {
        $this->cargarProtagonista($idUsuario, $idProtagonista, NivelRol::Responsable);
        $ficha = $this->familiares->buscarFicha($idProtagonista, $idFamiliar)
            ?? throw new NoEncontradoException('El familiar no está vinculado a este protagonista.');
            
        $quedariaSuelto = count($this->vinculos->listarPorFamiliar($idFamiliar)) === 1;
        
        if (!$quedariaSuelto) {
            $this->vinculos->eliminar($idProtagonista, $idFamiliar);
            return false;
        }
        if (!$confirmarIgual) {
            throw new RequisitosPendientesException([new Aviso(
                TipoAviso::FamiliarHuerfano,
                "{$ficha->persona->nombre} {$ficha->persona->apellido} no queda vinculada a ningún protagonista: se va a borrar su ficha de familiar.",
            )]);
        }
        $this->familiares->eliminar($idFamiliar);
        return true;
    }

    private function cargarProtagonista(int $idUsuario, int $idProtagonista, NivelRol $minimo): Protagonista
    {
        $protagonista = $this->protagonistas->buscarPorId($idProtagonista)
            ?? throw new NoEncontradoException('El protagonista no existe.');
        $this->permisos->exigirVisibleEnSeccion($idUsuario, $protagonista->idSeccion, $minimo, 'El protagonista no existe.');
        return $protagonista;
    }

    private function vincular(int $idProtagonista, int $idFamiliar, EsFamilia $vinculo): void
    {
        $vinculo->idProtagonista = $idProtagonista;
        $vinculo->idFamiliar = $idFamiliar;
        $this->vinculos->insertar($vinculo);
    }

    private function validarContacto(Familiar $familiar): void
    {
        $familiar->telefonoContacto = Validacion::textoOpcional($familiar->telefonoContacto, 30, 'El teléfono');
        $familiar->emailContacto = Validacion::emailOpcional($familiar->emailContacto);
    }

    private function validarVinculo(EsFamilia $vinculo): void
    {
        $vinculo->vinculo = Validacion::textoOpcional($vinculo->vinculo, 50, 'El vínculo');
    }
}