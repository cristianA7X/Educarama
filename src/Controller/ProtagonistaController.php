<?php
// src/Controller/ProtagonistaController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Model\Persona;
use Educarama\Model\Protagonista;
use Educarama\Service\DocumentoService;
use Educarama\Service\Excepcion\AccesoDenegadoException;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\FamiliarService;
use Educarama\Service\PatrullaService;
use Educarama\Service\ProgresionService;
use Educarama\Service\ProtagonistaService;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

/**
 * Protagonistas: listado de una sección, perfil, formulario de alta y edición,
 * pase de sección y baja.
 *
 * Páginas (GET, desde views/pages/):
 *   paginaListado     protagonistas.php?id_seccion=      Ayudante
 *   paginaPerfil      perfil_protagonista.php?id=        Ayudante
 *   paginaFormulario  protagonista_form.php?id_seccion=  (alta)    Responsable
 *                     protagonista_form.php?id=          (edición) Responsable
 *
 * Acciones (POST por fetch a public/api.php):
 *   protagonista.guardar         id (vacío = alta), id_seccion (solo alta), dni, nombre,
 *                                apellido, fecha_nacimiento, id_patrulla, promesa, anio_ingreso
 *   protagonista.cambiarSeccion  id_protagonista, id_seccion
 *   protagonista.eliminar        id_protagonista
 */
final class ProtagonistaController extends Controlador
{
    private ProtagonistaService $protagonistas;
    private SeccionService $secciones;
    private PatrullaService $patrullas;
    private ProgresionService $progresion;
    private FamiliarService $familiares;
    private DocumentoService $documentos;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?ProtagonistaService $protagonistas = null,
        ?SeccionService $secciones = null,
        ?PatrullaService $patrullas = null,
        ?ProgresionService $progresion = null,
        ?FamiliarService $familiares = null,
        ?DocumentoService $documentos = null,
    ) {
        parent::__construct($usuarioService);
        $this->protagonistas = $protagonistas ?? new ProtagonistaService();
        $this->secciones = $secciones ?? new SeccionService();
        $this->patrullas = $patrullas ?? new PatrullaService();
        $this->progresion = $progresion ?? new ProgresionService();
        $this->familiares = $familiares ?? new FamiliarService();
        $this->documentos = $documentos ?? new DocumentoService();
    }

    // -----------------------------------------------------------------
    // Páginas
    // -----------------------------------------------------------------

    /**
     * Nómina general: todos los protagonistas del usuario en una tabla con
     * progresión básica. Sin parámetros: muestra todas las secciones accesibles.
     *
     * Datos: 'nomina' (ProtagonistaEnNomina[]).
     */
    public function paginaNomina(): Respuesta
    {
        return Respuesta::pagina([
            'nomina' => $this->protagonistas->nomina($this->usuario()->id),
        ]);
    }

    /**
     * Datos: 'seccion' (SeccionEnPantalla), 'protagonistas' (FichaProtagonista[]),
     * 'patrullas' (Patrulla[], para mostrar y filtrar por patrulla).
     *
     * @param array<string, mixed> $get id_seccion
     */
    public function paginaListado(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::entero($get, 'id_seccion');

        $seccion = $this->secciones->encabezado($idUsuario, $idSeccion);  // 404 si no tiene acceso

        return Respuesta::pagina([
            'seccion'       => $seccion,
            'protagonistas' => $this->protagonistas->listarPorSeccion($idUsuario, $idSeccion),  // 403 si es Supervisor
            'patrullas'     => $this->patrullas->listarPorSeccion($idUsuario, $idSeccion),
            'nomina'        => $this->protagonistas->nominaPorSeccion($idUsuario, $idSeccion),
        ]);
    }

    /**
     * La página central del protagonista. Todo de lectura: las operaciones de
     * familiares y documentos tienen sus propias acciones.
     *
     * Datos: 'ficha', 'seccion' (SeccionEnPantalla, con los permisos para los
     * botones), 'resumen' (ResumenProgresion), 'familiares' (FichaFamiliar[],
     * primero los contactos de emergencia), 'documentos' (Documento[]) y
     * 'seccionesDestino' (SeccionEnPantalla[] para el pase; vacía si no puede).
     *
     * @param array<string, mixed> $get id
     */
    public function paginaPerfil(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($get, 'id');

        // Primero la ficha: si no tiene acceso, el 404 sale de acá.
        $ficha = $this->protagonistas->buscarFicha($idUsuario, $idProtagonista);

        return Respuesta::pagina([
            'ficha'            => $ficha,
            'seccion'          => $this->secciones->encabezado($idUsuario, $ficha->protagonista->idSeccion),
            'resumen'          => $this->progresion->resumen($idUsuario, $idProtagonista),
            'familiares'       => $this->familiares->listarPorProtagonista($idUsuario, $idProtagonista),
            'documentos'       => $this->documentos->listarPorProtagonista($idUsuario, $idProtagonista),
            'seccionesDestino' => $this->protagonistas->seccionesDestino($idUsuario, $idProtagonista),
        ]);
    }

    /**
     * Formulario de alta (?id_seccion=) o de edición (?id=).
     * Datos: 'seccion', 'patrullas' (para el select) y 'ficha' (null en un alta).
     *
     * @param array<string, mixed> $get id o id_seccion
     */
    public function paginaFormulario(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::enteroOpcional($get, 'id');

        $ficha = $idProtagonista === null ? null : $this->protagonistas->buscarFicha($idUsuario, $idProtagonista);
        $idSeccion = $ficha?->protagonista->idSeccion ?? self::entero($get, 'id_seccion');
        $seccion = $this->secciones->encabezado($idUsuario, $idSeccion);

        // El Service vuelve a controlar al guardar; esto evita mostrar un
        // formulario que después no se va a poder guardar.
        if (!$seccion->puedeEditar()) {
            throw new AccesoDenegadoException('Necesitás el rol Responsable en esta sección para hacer esto.');
        }

        return Respuesta::pagina([
            'seccion'   => $seccion,
            'patrullas' => $this->patrullas->listarPorSeccion($idUsuario, $idSeccion),
            'ficha'     => $ficha,
        ]);
    }

    // -----------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------

    /**
     * Alta si no viene id; edición si viene. Redirige al perfil.
     *
     * En la edición la sección sale de la base, no del formulario: para
     * cambiarla está cambiarSeccion().
     *
     * @param array<string, mixed> $post
     */
    public function guardar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $id = self::enteroOpcional($post, 'id');

        $idSeccion = $id === null
            ? self::entero($post, 'id_seccion')
            : $this->protagonistas->buscarFicha($idUsuario, $id)->protagonista->idSeccion;

        $persona = new Persona(
            id: null,
            dni: self::dni($post),
            nombre: self::texto($post, 'nombre'),
            apellido: self::texto($post, 'apellido'),
            fechaNacimiento: self::fechaOpcional($post, 'fecha_nacimiento'),
        );
        $protagonista = new Protagonista(
            id: $id,
            idPersona: 0,  // Lo completa el Service (alta) o lo toma de la base (edición)
            idSeccion: $idSeccion,
            idPatrulla: self::enteroOpcional($post, 'id_patrulla'),
            promesa: self::booleano($post, 'promesa'),
            anioIngreso: self::anio($post, 'anio_ingreso'),
        );

        if ($id === null) {
            $id = $this->protagonistas->alta($idUsuario, $persona, $protagonista);
            Flash::guardar(Flash::EXITO, 'Protagonista dado de alta.');
        } else {
            $this->protagonistas->actualizar($idUsuario, $persona, $protagonista);
            Flash::guardar(Flash::EXITO, 'Cambios guardados.');
        }

        return Respuesta::exito(['id' => $id], redireccion: url('views/pages/perfil_protagonista.php?id=' . $id));
    }

    /** @param array<string, mixed> $post */
    public function cambiarSeccion(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($post, 'id_protagonista');
        $idSeccion = self::entero($post, 'id_seccion');

        $this->protagonistas->cambiarSeccion($idUsuario, $idProtagonista, $idSeccion);

        // "Pasó a Unidad Aconcagua. Quedó sin patrulla." (Rovers no tiene pequeño equipo: sin la segunda parte)
        $destino = $this->secciones->encabezado($idUsuario, $idSeccion);
        $equipo = $destino->pequenioEquipo();
        Flash::guardar(Flash::EXITO, "Pasó a {$destino->seccion->nombre}." . ($equipo === null ? '' : ' Quedó sin ' . mb_strtolower($equipo) . '.'));

        return Respuesta::exito(redireccion: url('views/pages/perfil_protagonista.php?id=' . $idProtagonista));
    }

    /** Baja definitiva (Jefe). Redirige al listado de su sección. @param array<string, mixed> $post */
    public function eliminar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($post, 'id_protagonista');

        // La sección y el nombre se leen antes de borrar, para el redirect y el mensaje.
        $ficha = $this->protagonistas->buscarFicha($idUsuario, $idProtagonista);
        $this->protagonistas->eliminar($idUsuario, $idProtagonista);

        Flash::guardar(Flash::EXITO, "Se eliminó a {$ficha->persona->nombre} {$ficha->persona->apellido}.");
        return Respuesta::exito(redireccion: url('views/pages/protagonistas.php?id_seccion=' . $ficha->protagonista->idSeccion));
    }

    // -----------------------------------------------------------------
    // Lectura del formulario
    // -----------------------------------------------------------------

    /** Año opcional (vacío = null). Si no es un número, error para el usuario (no "Faltan datos"). */
    private static function anio(array $post, string $clave): ?int
    {
        $texto = trim(self::texto($post, $clave));
        if ($texto === '') {
            return null;
        }
        if (!ctype_digit($texto) || strlen($texto) > 4) {
            throw new ReglaNegocioException('El año de ingreso no es válido.');
        }
        return (int) $texto;
    }
}
