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

/**
 * Familiares, siempre desde un protagonista (CLAUDE.md, "Familiares"). La
 * lista se ve en el perfil del protagonista; acá están el formulario y las acciones.
 *
 * Página (GET):
 *   paginaFormulario  familiar_form.php?id_protagonista=               (alta)    Responsable
 *                     familiar_form.php?id_protagonista=&id_familiar=  (edición) Responsable
 *
 * Acciones (POST por fetch a public/api.php). Todas vuelven al perfil del protagonista:
 *   familiar.guardar      id_protagonista, id_familiar (vacío = alta), dni, nombre, apellido,
 *                         fecha_nacimiento, telefono, email, vinculo, emergencia, confirmarIgual
 *   familiar.desvincular  id_protagonista, id_familiar, confirmarIgual
 *
 * Avisos que se confirman (409 → el JS repite con confirmarIgual=1):
 *   - alta con un DNI ya cargado: "Ana Pérez ya está cargada con ese DNI. ¿Vincularla...?"
 *   - desvincular al último protagonista: "...se va a borrar su ficha de familiar."
 */
final class FamiliarController extends Controlador
{
    private FamiliarService $familiares;
    private ProtagonistaService $protagonistas;
    private SeccionService $secciones;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?FamiliarService $familiares = null,
        ?ProtagonistaService $protagonistas = null,
        ?SeccionService $secciones = null,
    ) {
        parent::__construct($usuarioService);
        $this->familiares = $familiares ?? new FamiliarService();
        $this->protagonistas = $protagonistas ?? new ProtagonistaService();
        $this->secciones = $secciones ?? new SeccionService();
    }

    // -----------------------------------------------------------------
    // Página
    // -----------------------------------------------------------------

    /**
     * Datos: 'protagonista' (FichaProtagonista), 'seccion' (SeccionEnPantalla),
     * 'familiar' (FichaFamiliar, null en un alta) y 'compartido' (bool: si
     * también está vinculado a otros protagonistas, para avisar que los cambios
     * de datos y contacto se ven en todos; nunca cuántos ni cuáles).
     *
     * @param array<string, mixed> $get id_protagonista, id_familiar (opcional)
     */
    public function paginaFormulario(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($get, 'id_protagonista');
        $idFamiliar = self::enteroOpcional($get, 'id_familiar');

        $protagonista = $this->protagonistas->buscarFicha($idUsuario, $idProtagonista);  // 404 si no tiene acceso
        $seccion = $this->secciones->encabezado($idUsuario, $protagonista->protagonista->idSeccion);

        // El Service vuelve a controlar al guardar; esto evita mostrar un
        // formulario que después no se va a poder guardar.
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

    // -----------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------

    /**
     * Alta si no viene id_familiar; edición si viene.
     *
     * @param array<string, mixed> $post
     */
    public function guardar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($post, 'id_protagonista');
        $idFamiliar = self::enteroOpcional($post, 'id_familiar');

        // Los ids de los Models se ignoran o se fuerzan desde la base en el Service.
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

    /** @param array<string, mixed> $post */
    public function desvincular(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($post, 'id_protagonista');
        $idFamiliar = self::entero($post, 'id_familiar');

        // El nombre se lee antes: si era su último protagonista, la ficha se borra.
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
