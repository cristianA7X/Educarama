<?php
// src/Controller/FamiliarController.php
declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Model\EsFamilia;
use Educarama\Model\Familiar;
use Educarama\Model\Persona;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\FamiliarService;
use Educarama\Service\ProtagonistaService;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

final class FamiliarController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly FamiliarService $familiares,
        private readonly ProtagonistaService $protagonistas,
        private readonly SeccionService $secciones
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaFormulario(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($get, 'id_protagonista');
        $idFamiliar = self::enteroOpcional($get, 'id_familiar');
        
        $protagonista = $this->protagonistas->buscarFicha($idUsuario, $idProtagonista);
        $seccion = $this->secciones->encabezado($idUsuario, $protagonista->protagonista->idSeccion);
        
        if (!$seccion->puedeEditar()) {
            throw new AccesoDenegadoException('Necesitás el rol Responsable en esta sección para hacer esto.');
        }
        
        return Respuesta::pagina([
            'protagonista' => $protagonista,
            'seccion'      => $seccion,
            'familiar'     => $idFamiliar === null ? null : $this->familiares->buscarFicha($idUsuario, $idProtagonista, $idFamiliar),
            'compartido'   => $idFamiliar !== null && $this->familiares->vinculadoAOtros($idUsuario, $idProtagonista, $idFamiliar),
        ]);
    }

    public function guardar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($post, 'id_protagonista');
        $idFamiliar = self::enteroOpcional($post, 'id_familiar');
        
        $persona = new Persona(
            id: null,
            dni: self::dni($post),
            nombre: self::texto($post, 'nombre'),
            apellido: self::texto($post, 'apellido'),
            fechaNacimiento: self::fechaOpcional($post, 'fecha_nacimiento'),
        );
        $familiar = new Familiar(
            id: $idFamiliar,
            idPersona: 0,
            telefonoContacto: self::texto($post, 'telefono'),
            emailContacto: self::texto($post, 'email'),
        );
        $vinculo = new EsFamilia(
            idProtagonista: $idProtagonista,
            idFamiliar: $idFamiliar ?? 0,
            vinculo: self::texto($post, 'vinculo'),
            esContactoDeEmergencia: self::booleano($post, 'emergencia'),
        );
        
        if ($idFamiliar === null) {
            $this->familiares->alta($idUsuario, $idProtagonista, $persona, $familiar, $vinculo, self::booleano($post, 'confirmarIgual'));
            Flash::guardar(Flash::EXITO, 'Familiar agregado.');
        } else {
            $this->familiares->actualizar($idUsuario, $idProtagonista, $persona, $familiar, $vinculo);
            Flash::guardar(Flash::EXITO, 'Cambios guardados.');
        }
        
        return Respuesta::exito(redireccion: self::perfil($idProtagonista));
    }

    public function desvincular(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($post, 'id_protagonista');
        $idFamiliar = self::entero($post, 'id_familiar');
        
        $nombre = $this->familiares->buscarFicha($idUsuario, $idProtagonista, $idFamiliar)->persona;
        $borrada = $this->familiares->desvincular($idUsuario, $idProtagonista, $idFamiliar, self::booleano($post, 'confirmarIgual'));
        
        Flash::guardar(Flash::EXITO, "Se desvinculó a {$nombre->nombre} {$nombre->apellido}" . ($borrada ? ' y se borró su ficha de familiar.' : '.'));
        return Respuesta::exito(redireccion: self::perfil($idProtagonista));
    }

    private static function perfil(int $idProtagonista): string
    {
        return url('views/pages/perfil_protagonista.php?id=' . $idProtagonista);
    }
}