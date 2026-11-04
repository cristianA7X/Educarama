<?php
// src/Controller/ProgresionController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Model\Enum\Elemento;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\ProgresionService;
use Educarama\Service\ProtagonistaService;
use Educarama\Service\UsuarioService;
use Educarama\Service\Vista\Aviso;
use Educarama\Service\Vista\ResultadoProgresion;
use Educarama\Service\Vista\ResumenProgresion;

/**
 * Progresión personal: la pantalla de competencias y todas las operaciones
 * de guardado inmediato (un clic = un pedido por fetch).
 *
 * Página (GET):
 *   paginaCompetencias   gestionar_competencias.php?id=...   Ayudante en adelante
 *
 * Acciones (POST por fetch a public/api.php). Todas reciben id_protagonista y
 * devuelven {sugerencias, resumen}: el resumen actualizado sirve para repintar
 * los contadores, lo adeudado y el aviso de insignia sin recargar la página.
 *
 *   progresion.avanzarCompetencia      id_competencia, confirmarIgual
 *   progresion.retrocederCompetencia   id_competencia
 *   progresion.marcarAccion            id_accion
 *   progresion.desmarcarAccion         id_accion
 *   progresion.marcarTarea             id_tarea_etapa
 *   progresion.desmarcarTarea          id_tarea_etapa
 *   progresion.confirmarEtapa          id_etapa, elemento (Caminantes), fecha (opcional), confirmarIgual
 *   progresion.quitarEtapa             id_etapa
 *   progresion.otorgarInsignia         id_insignia, fecha (opcional)
 *   progresion.quitarInsignia          id_insignia
 *   progresion.avanzarEspecialidad     id_especialidad
 *   progresion.retrocederEspecialidad  id_especialidad
 *
 * Si faltan requisitos que se pueden saltear, el Service lanza
 * RequisitosPendientesException y Api responde 409 con los avisos; el JS
 * pregunta y repite el mismo pedido con confirmarIgual=1.
 *
 * Fecha: solo confirmarEtapa y otorgarInsignia la aceptan (la ceremonia o la
 * entrega física pueden ser de otro día). Vacía = hoy; futura, no.
 */
final class ProgresionController extends Controlador
{
    private ProgresionService $progresion;
    private ProtagonistaService $protagonistas;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?ProgresionService $progresion = null,
        ?ProtagonistaService $protagonistas = null,
    ) {
        parent::__construct($usuarioService);
        $this->progresion = $progresion ?? new ProgresionService();
        $this->protagonistas = $protagonistas ?? new ProtagonistaService();
    }

    // -----------------------------------------------------------------
    // Página
    // -----------------------------------------------------------------

    /**
     * Pantalla de competencias. Datos: 'ficha' (FichaProtagonista: nombre,
     * sección, patrulla) y 'pantalla' (PantallaCompetencias).
     *
     * @param array<string, mixed> $get id
     */
    public function paginaCompetencias(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($get, 'id');

        // Primero la pantalla: si no tiene acceso, el 404 sale de acá.
        $pantalla = $this->progresion->pantallaCompetencias($idUsuario, $idProtagonista);

        return Respuesta::pagina([
            'ficha'    => $this->protagonistas->buscarFicha($idUsuario, $idProtagonista),
            'pantalla' => $pantalla,
        ]);
    }

    // -----------------------------------------------------------------
    // Competencias
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
    public function avanzarCompetencia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->avanzarCompetencia(
            $u, $p, self::entero($post, 'id_competencia'), self::booleano($post, 'confirmarIgual'),
        ));
    }

    /** @param array<string, mixed> $post */
    public function retrocederCompetencia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->retrocederCompetencia(
            $u, $p, self::entero($post, 'id_competencia'),
        ));
    }

    // -----------------------------------------------------------------
    // Acciones y tareas
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
    public function marcarAccion(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->marcarAccion(
            $u, $p, self::entero($post, 'id_accion'),
        ));
    }

    /** @param array<string, mixed> $post */
    public function desmarcarAccion(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->desmarcarAccion(
            $u, $p, self::entero($post, 'id_accion'),
        ));
    }

    /** @param array<string, mixed> $post */
    public function marcarTarea(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->marcarTarea(
            $u, $p, self::entero($post, 'id_tarea_etapa'),
        ));
    }

    /** @param array<string, mixed> $post */
    public function desmarcarTarea(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->desmarcarTarea(
            $u, $p, self::entero($post, 'id_tarea_etapa'),
        ));
    }

    // -----------------------------------------------------------------
    // Etapas e insignias
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
    public function confirmarEtapa(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->confirmarEtapa(
            $u, $p,
            self::entero($post, 'id_etapa'),
            self::elemento($post),
            self::booleano($post, 'confirmarIgual'),
            self::fechaOpcional($post),
        ));
    }

    /** @param array<string, mixed> $post */
    public function quitarEtapa(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->quitarEtapa(
            $u, $p, self::entero($post, 'id_etapa'),
        ));
    }

    /** @param array<string, mixed> $post */
    public function otorgarInsignia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->otorgarInsignia(
            $u, $p, self::entero($post, 'id_insignia'), self::fechaOpcional($post),
        ));
    }

    /** @param array<string, mixed> $post */
    public function quitarInsignia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->quitarInsignia(
            $u, $p, self::entero($post, 'id_insignia'),
        ));
    }

    // -----------------------------------------------------------------
    // Especialidades
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
    public function avanzarEspecialidad(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->avanzarEspecialidad(
            $u, $p, self::entero($post, 'id_especialidad'),
        ));
    }

    /** @param array<string, mixed> $post */
    public function retrocederEspecialidad(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->retrocederEspecialidad(
            $u, $p, self::entero($post, 'id_especialidad'),
        ));
    }

    // -----------------------------------------------------------------
    // Comunes
    // -----------------------------------------------------------------

    /**
     * Esqueleto de todas las acciones: saca el usuario y el protagonista, hace
     * la operación y responde con sus sugerencias más el resumen actualizado.
     *
     * @param array<string, mixed> $post
     * @param callable(int $idUsuario, int $idProtagonista): (ResultadoProgresion|null) $operacion
     */
    private function operar(array $post, callable $operacion): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($post, 'id_protagonista');

        $resultado = $operacion($idUsuario, $idProtagonista);
        $sugerencias = $resultado instanceof ResultadoProgresion ? $resultado->sugerencias : [];

        return Respuesta::exito([
            'sugerencias' => self::avisos($sugerencias),
            'resumen'     => self::resumenParaJs($this->progresion->resumen($idUsuario, $idProtagonista)),
        ]);
    }

    /**
     * El resumen como lo usa el JS: solo números, ids y textos (no Models enteros).
     *
     * @return array<string, mixed>
     */
    private static function resumenParaJs(ResumenProgresion $r): array
    {
        $etapa = fn ($e) => $e === null ? null : ['id' => $e->id, 'titulo' => $e->titulo, 'cupo' => $e->competenciasEtapa];

        return [
            'etapaActual'          => $etapa($r->etapaActual),
            'etapaEnCurso'         => $etapa($r->etapaEnCurso),
            'enCursoElegidas'      => $r->enCursoElegidas,
            'enCursoDesarrolladas' => $r->enCursoDesarrolladas,
            'desarrolladasTotal'   => $r->desarrolladasTotal,
            'competenciasTotal'    => $r->competenciasTotal,
            'etapaLista'           => $r->etapaLista(),
            'areas'                => array_map(fn ($a) => [
                'idArea'        => $a->idArea,
                'desarrolladas' => $a->desarrolladas,
                'total'         => $a->total,
            ], $r->areas),
            'adeudado'             => self::avisos($r->adeudado),
            'insigniaPendiente'    => $r->insigniaPendiente === null ? null : [
                'id'     => $r->insigniaPendiente->id,
                'titulo' => $r->insigniaPendiente->titulo,
            ],
        ];
    }

    /**
     * @param Aviso[] $avisos
     * @return list<array{tipo: string, mensaje: string}>
     */
    private static function avisos(array $avisos): array
    {
        return array_map(fn (Aviso $a) => ['tipo' => $a->tipo->value, 'mensaje' => $a->mensaje], array_values($avisos));
    }

    /**
     * Elemento de Caminantes ('agua', 'tierra', 'fuego', 'aire'); vacío = null.
     * Si es null en Caminantes, o viene en otra rama, lo rechaza el Service.
     *
     * @throws ReglaNegocioException
     */
    private static function elemento(array $post): ?Elemento
    {
        $texto = self::texto($post, 'elemento');
        if ($texto === '') {
            return null;
        }
        return Elemento::tryFrom($texto)
            ?? throw new ReglaNegocioException('El elemento elegido no es válido.');
    }

    /** Fecha 'AAAA-MM-DD' o null (= hoy). La valida el Service (formato y no futura). */
    private static function fechaOpcional(array $post): ?string
    {
        $texto = trim(self::texto($post, 'fecha'));
        return $texto === '' ? null : $texto;
    }
}
