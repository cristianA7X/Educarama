<?php
// src/Controller/PatrullaController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Model\Patrulla;
use Educarama\Repository\Vista\FichaProtagonista;
use Educarama\Service\PatrullaService;
use Educarama\Service\ProtagonistaService;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;
use Educarama\Service\Vista\SeccionEnPantalla;

/**
 * Pequeños equipos de una sección: Seisena (Manada), Patrulla (Unidad) o
 * Equipo (Caminantes). El término sale de nomenclatura; en el código se llaman
 * "patrulla" siempre.
 *
 * Páginas (GET, desde views/pages/):
 *   paginaListado  patrullas.php?id_seccion=   Ayudante
 *   paginaPerfil   perfil_patrulla.php?id=     Ayudante
 *
 * Acciones (POST por fetch a public/api.php):
 *   patrulla.crear              id_seccion, nombre        → al perfil de la nueva
 *   patrulla.renombrar          id_patrulla, nombre
 *   patrulla.eliminar           id_patrulla               → al listado (Jefe)
 *   patrulla.asignarIntegrante  id_patrulla, id_protagonista
 *   patrulla.quitarIntegrante   id_patrulla, id_protagonista
 */
final class PatrullaController extends Controlador
{
    private PatrullaService $patrullas;
    private ProtagonistaService $protagonistas;
    private SeccionService $secciones;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?PatrullaService $patrullas = null,
        ?ProtagonistaService $protagonistas = null,
        ?SeccionService $secciones = null,
    ) {
        parent::__construct($usuarioService);
        $this->patrullas = $patrullas ?? new PatrullaService();
        $this->protagonistas = $protagonistas ?? new ProtagonistaService();
        $this->secciones = $secciones ?? new SeccionService();
    }

    // -----------------------------------------------------------------
    // Páginas
    // -----------------------------------------------------------------

    /**
     * Datos: 'seccion', 'patrullas' (Patrulla[]), 'integrantes' (id de patrulla
     * => FichaProtagonista[]) y 'sinPatrulla' (FichaProtagonista[]).
     *
     * @param array<string, mixed> $get id_seccion
     */
    public function paginaListado(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::entero($get, 'id_seccion');

        $seccion = $this->secciones->encabezado($idUsuario, $idSeccion);
        $patrullas = $this->patrullas->listarPorSeccion($idUsuario, $idSeccion);

        // Una sola consulta de protagonistas, repartida por patrulla.
        $integrantes = array_fill_keys(array_map(fn (Patrulla $p) => $p->id, $patrullas), []);
        $sinPatrulla = [];
        foreach ($this->protagonistas->listarPorSeccion($idUsuario, $idSeccion) as $ficha) {
            $idPatrulla = $ficha->protagonista->idPatrulla;
            if ($idPatrulla === null || !isset($integrantes[$idPatrulla])) {
                $sinPatrulla[] = $ficha;
            } else {
                $integrantes[$idPatrulla][] = $ficha;
            }
        }

        return Respuesta::pagina([
            'seccion'     => $seccion,
            'patrullas'   => $patrullas,
            'integrantes' => $integrantes,
            'sinPatrulla' => $sinPatrulla,
        ]);
    }

    /**
     * Datos: 'patrulla', 'seccion', 'integrantes' (FichaProtagonista[]) y
     * 'candidatos' (los demás protagonistas de la sección, para "Agregar";
     * su nombrePatrulla dice si ya están en otra).
     *
     * @param array<string, mixed> $get id
     */
    public function paginaPerfil(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $patrulla = $this->patrullas->buscar($idUsuario, self::entero($get, 'id'));  // 404 si no tiene acceso

        $candidatos = array_values(array_filter(
            $this->protagonistas->listarPorSeccion($idUsuario, $patrulla->idSeccion),
            fn (FichaProtagonista $f) => $f->protagonista->idPatrulla !== $patrulla->id,
        ));

        return Respuesta::pagina([
            'patrulla'    => $patrulla,
            'seccion'     => $this->secciones->encabezado($idUsuario, $patrulla->idSeccion),
            'integrantes' => $this->patrullas->integrantes($idUsuario, (int) $patrulla->id),
            'candidatos'  => $candidatos,
        ]);
    }

    // -----------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
    public function crear(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::entero($post, 'id_seccion');

        $id = $this->patrullas->crear($idUsuario, new Patrulla(null, self::texto($post, 'nombre'), $idSeccion));

        // Sin adjetivos con género: Seisena y Patrulla son femeninos, Equipo masculino.
        Flash::guardar(Flash::EXITO, 'Se creó «' . trim(self::texto($post, 'nombre')) . '».');
        return Respuesta::exito(['id' => $id], redireccion: url('views/pages/perfil_patrulla.php?id=' . $id));
    }

    /** @param array<string, mixed> $post */
    public function renombrar(array $post): Respuesta
    {
        $this->patrullas->renombrar($this->usuario()->id, self::entero($post, 'id_patrulla'), self::texto($post, 'nombre'));
        return Respuesta::exito(mensaje: 'Nombre cambiado.');
    }

    /** Borra la patrulla (Jefe); sus integrantes quedan sin patrulla. @param array<string, mixed> $post */
    public function eliminar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $patrulla = $this->patrullas->buscar($idUsuario, self::entero($post, 'id_patrulla'));
        $seccion = $this->secciones->encabezado($idUsuario, $patrulla->idSeccion);

        $this->patrullas->eliminar($idUsuario, (int) $patrulla->id);

        Flash::guardar(Flash::EXITO, "Se eliminó «{$patrulla->nombre}». Sus integrantes quedaron sin " . mb_strtolower(self::termino($seccion)) . '.');
        return Respuesta::exito(redireccion: url('views/pages/patrullas.php?id_seccion=' . $patrulla->idSeccion));
    }

    /**
     * Suma al protagonista a la patrulla. Si estaba en otra, se pasa a esta.
     *
     * @param array<string, mixed> $post
     */
    public function asignarIntegrante(array $post): Respuesta
    {
        $this->patrullas->asignarIntegrante(
            $this->usuario()->id,
            self::entero($post, 'id_protagonista'),
            self::entero($post, 'id_patrulla'),
        );
        return Respuesta::exito();
    }

    /** @param array<string, mixed> $post */
    public function quitarIntegrante(array $post): Respuesta
    {
        $this->patrullas->quitarIntegrante(
            $this->usuario()->id,
            self::entero($post, 'id_patrulla'),
            self::entero($post, 'id_protagonista'),
        );
        return Respuesta::exito();
    }

    // -----------------------------------------------------------------

    /** "Seisena", "Patrulla" o "Equipo" según la rama de la sección. */
    private static function termino(SeccionEnPantalla $seccion): string
    {
        return $seccion->pequenioEquipo() ?? 'Patrulla';
    }
}
