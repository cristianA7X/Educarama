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

final class ProgresionController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly ProgresionService $progresion,
        private readonly ProtagonistaService $protagonistas
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaCompetencias(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($get, 'id');

        $pantalla = $this->progresion->pantallaCompetencias($idUsuario, $idProtagonista);

        return Respuesta::pagina([
            'ficha'    => $this->protagonistas->buscarFicha($idUsuario, $idProtagonista),
            'pantalla' => $pantalla,
        ]);
    }

    public function paginaEtapas(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idProtagonista = self::entero($get, 'id');

        $pantalla = $this->progresion->pantallaEtapas($idUsuario, $idProtagonista);

        return Respuesta::pagina([
            'ficha'    => $this->protagonistas->buscarFicha($idUsuario, $idProtagonista),
            'pantalla' => $pantalla,
        ]);
    }

    public function avanzarCompetencia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->avanzarCompetencia(
            $u, $p, self::entero($post, 'id_competencia'), self::booleano($post, 'confirmarIgual'),
        ));
    }

    public function retrocederCompetencia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->retrocederCompetencia(
            $u, $p, self::entero($post, 'id_competencia'),
        ));
    }

    public function marcarAccion(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->marcarAccion(
            $u, $p, self::entero($post, 'id_accion'),
        ));
    }

    public function desmarcarAccion(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->desmarcarAccion(
            $u, $p, self::entero($post, 'id_accion'),
        ));
    }

    public function marcarTarea(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->marcarTarea(
            $u, $p, self::entero($post, 'id_tarea_etapa'),
        ));
    }

    public function desmarcarTarea(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->desmarcarTarea(
            $u, $p, self::entero($post, 'id_tarea_etapa'),
        ));
    }

    public function confirmarEtapa(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->confirmarEtapa(
            $u, $p,
            self::entero($post, 'id_etapa'),
            self::elemento($post),
            self::booleano($post, 'confirmarIgual'),
            self::fechaOpcional($post, 'fecha'),
        ));
    }

    public function quitarEtapa(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->quitarEtapa(
            $u, $p, self::entero($post, 'id_etapa'),
        ));
    }

    public function otorgarInsignia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->otorgarInsignia(
            $u, $p, self::entero($post, 'id_insignia'), self::fechaOpcional($post, 'fecha'),
        ));
    }

    public function quitarInsignia(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->quitarInsignia(
            $u, $p, self::entero($post, 'id_insignia'),
        ));
    }

    public function avanzarEspecialidad(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->avanzarEspecialidad(
            $u, $p, self::entero($post, 'id_especialidad'),
        ));
    }

    public function retrocederEspecialidad(array $post): Respuesta
    {
        return $this->operar($post, fn (int $u, int $p) => $this->progresion->retrocederEspecialidad(
            $u, $p, self::entero($post, 'id_especialidad'),
        ));
    }

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

    private static function avisos(array $avisos): array
    {
        return array_map(fn (Aviso $a) => ['tipo' => $a->tipo->value, 'mensaje' => $a->mensaje], array_values($avisos));
    }

    private static function elemento(array $post): ?Elemento
    {
        $texto = self::texto($post, 'elemento');
        if ($texto === '') {
            return null;
        }
        return Elemento::tryFrom($texto)
            ?? throw new ReglaNegocioException('El elemento elegido no es válido.');
    }
}