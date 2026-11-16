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

final class PatrullaController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly PatrullaService $patrullas,
        private readonly ProtagonistaService $protagonistas,
        private readonly SeccionService $secciones
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaListado(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::entero($get, 'id_seccion');

        $seccion = $this->secciones->encabezado($idUsuario, $idSeccion);
        $patrullas = $this->patrullas->listarPorSeccion($idUsuario, $idSeccion);

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

    public function paginaPerfil(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $patrulla = $this->patrullas->buscar($idUsuario, self::entero($get, 'id'));

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

    public function crear(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::entero($post, 'id_seccion');

        $id = $this->patrullas->crear($idUsuario, new Patrulla(null, self::texto($post, 'nombre'), $idSeccion));

        Flash::guardar(Flash::EXITO, 'Se creó «' . trim(self::texto($post, 'nombre')) . '».');
        return Respuesta::exito(['id' => $id], redireccion: url('views/pages/perfil_patrulla.php?id=' . $id));
    }

    public function renombrar(array $post): Respuesta
    {
        $this->patrullas->renombrar($this->usuario()->id, self::entero($post, 'id_patrulla'), self::texto($post, 'nombre'));
        return Respuesta::exito(mensaje: 'Nombre cambiado.');
    }

    public function eliminar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $patrulla = $this->patrullas->buscar($idUsuario, self::entero($post, 'id_patrulla'));
        $seccion = $this->secciones->encabezado($idUsuario, $patrulla->idSeccion);

        $this->patrullas->eliminar($idUsuario, (int) $patrulla->id);

        Flash::guardar(Flash::EXITO, "Se eliminó «{$patrulla->nombre}». Sus integrantes quedaron sin " . mb_strtolower(self::termino($seccion)) . '.');
        return Respuesta::exito(redireccion: url('views/pages/patrullas.php?id_seccion=' . $patrulla->idSeccion));
    }

    public function asignarIntegrante(array $post): Respuesta
    {
        $this->patrullas->asignarIntegrante(
            $this->usuario()->id,
            self::entero($post, 'id_protagonista'),
            self::entero($post, 'id_patrulla'),
        );
        return Respuesta::exito();
    }

    public function quitarIntegrante(array $post): Respuesta
    {
        $this->patrullas->quitarIntegrante(
            $this->usuario()->id,
            self::entero($post, 'id_patrulla'),
            self::entero($post, 'id_protagonista'),
        );
        return Respuesta::exito();
    }

    private static function termino(SeccionEnPantalla $seccion): string
    {
        return $seccion->pequenioEquipo() ?? 'Patrulla';
    }
}