<?php
// src/Controller/AccesoController.php

declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Helper\Flash;
use Educarama\Model\Enum\NivelRol;
use Educarama\Service\AccesoService;
use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\SeccionService;
use Educarama\Service\UsuarioService;

/**
 * Gestión de roles: quién tiene acceso a un grupo o a una sección (CLAUDE.md,
 * "Accesos"). Una sola página con dos modos, como la de eventos.
 *
 * Página (GET):
 *   paginaListado  accesos.php?id_grupo=     Lista del grupo (Jefe de grupo)
 *                  accesos.php?id_seccion=   Lista de la sección (su Jefe y el Jefe de grupo)
 *
 * Acciones (POST por fetch a public/api.php). Todas llevan "ambito" ('grupo' o
 * 'seccion') y el id de ese grupo o sección (id_grupo / id_seccion), y vuelven
 * a la lista con un Flash:
 *   acceso.dar          email, rol (1 a 4)      Sumar a una cuenta ya registrada
 *   acceso.cambiarRol   id_usuario, rol
 *   acceso.quitar       id_usuario
 *
 * Las reglas (último Jefe, Jefe de sección hasta Responsable...) son del
 * Service; la pantalla ya viene con los botones que corresponden a cada fila.
 */
final class AccesoController extends Controlador
{
    private AccesoService $accesos;
    private SeccionService $secciones;

    public function __construct(
        ?UsuarioService $usuarioService = null,
        ?AccesoService $accesos = null,
        ?SeccionService $secciones = null,
    ) {
        parent::__construct($usuarioService);
        $this->accesos = $accesos ?? new AccesoService();
        $this->secciones = $secciones ?? new SeccionService();
    }

    // -----------------------------------------------------------------
    // Página
    // -----------------------------------------------------------------

    /**
     * Datos: 'modo' ('grupo' | 'seccion'), 'grupo' (GrupoEnPantalla, en modo
     * grupo) o 'seccion' (SeccionEnPantalla, en modo sección) y 'pantalla'
     * (AccesosEnPantalla: filas con sus botones y los roles para agregar).
     *
     * @param array<string, mixed> $get id_grupo o id_seccion
     */
    public function paginaListado(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::enteroOpcional($get, 'id_seccion');

        if ($idSeccion !== null) {
            $pantalla = $this->accesos->pantallaSeccion($idUsuario, $idSeccion);  // 404 sin acceso, 403 si no es Jefe

            return Respuesta::pagina([
                'modo'     => 'seccion',
                'seccion'  => $this->secciones->encabezado($idUsuario, $idSeccion),
                'pantalla' => $pantalla,
            ]);
        }

        $idGrupo = self::entero($get, 'id_grupo');
        $pantalla = $this->accesos->pantallaGrupo($idUsuario, $idGrupo);

        return Respuesta::pagina([
            'modo'     => 'grupo',
            'grupo'    => $this->secciones->grupo($idUsuario, $idGrupo),
            'pantalla' => $pantalla,
        ]);
    }

    // -----------------------------------------------------------------
    // Acciones
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $post */
    public function dar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        [$ambito, $id] = self::ambito($post);
        $email = self::texto($post, 'email');
        $nivel = self::nivel($post);

        $ambito === 'grupo'
            ? $this->accesos->darAccesoGrupo($idUsuario, $id, $email, $nivel)
            : $this->accesos->darAccesoSeccion($idUsuario, $id, $email, $nivel);

        Flash::guardar(Flash::EXITO, 'Acceso agregado.');
        return Respuesta::exito(redireccion: self::lista($ambito, $id));
    }

    /** @param array<string, mixed> $post */
    public function cambiarRol(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        [$ambito, $id] = self::ambito($post);
        $idDestino = self::entero($post, 'id_usuario');
        $nivel = self::nivel($post);

        $ambito === 'grupo'
            ? $this->accesos->cambiarRolGrupo($idUsuario, $id, $idDestino, $nivel)
            : $this->accesos->cambiarRolSeccion($idUsuario, $id, $idDestino, $nivel);

        Flash::guardar(Flash::EXITO, 'Rol cambiado.');

        // Si el Jefe de grupo se baja el rol a sí mismo, ya no puede ver la lista: va a la pantalla del grupo.
        if ($ambito === 'grupo' && $idDestino === $idUsuario && $nivel !== NivelRol::Jefe) {
            return Respuesta::exito(redireccion: url('views/pages/grupo.php?id=' . $id));
        }

        return Respuesta::exito(redireccion: self::lista($ambito, $id));
    }

    /** @param array<string, mixed> $post */
    public function quitar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        [$ambito, $id] = self::ambito($post);
        $idDestino = self::entero($post, 'id_usuario');

        $ambito === 'grupo'
            ? $this->accesos->quitarAccesoGrupo($idUsuario, $id, $idDestino)
            : $this->accesos->quitarAccesoSeccion($idUsuario, $id, $idDestino);

        Flash::guardar(Flash::EXITO, 'Acceso quitado.');

        // Si el Jefe de grupo se quita a sí mismo (había otro Jefe), ya no ve la lista: va al inicio.
        if ($ambito === 'grupo' && $idDestino === $idUsuario) {
            return Respuesta::exito(redireccion: url('views/pages/inicio.php'));
        }

        return Respuesta::exito(redireccion: self::lista($ambito, $id));
    }

    // -----------------------------------------------------------------

    /**
     * El ámbito explícito ('grupo' o 'seccion') y el id que le corresponde.
     *
     * @return array{0: string, 1: int}
     * @throws ReglaNegocioException
     */
    private static function ambito(array $post): array
    {
        return match (self::texto($post, 'ambito')) {
            'grupo'   => ['grupo', self::entero($post, 'id_grupo')],
            'seccion' => ['seccion', self::entero($post, 'id_seccion')],
            default   => throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.'),
        };
    }

    /** El rol elegido (1 a 4). @throws ReglaNegocioException */
    private static function nivel(array $post): NivelRol
    {
        return NivelRol::tryFrom(self::entero($post, 'rol'))
            ?? throw new ReglaNegocioException('El rol elegido no es válido.');
    }

    private static function lista(string $ambito, int $id): string
    {
        return url('views/pages/accesos.php?' . ($ambito === 'grupo' ? 'id_grupo=' : 'id_seccion=') . $id);
    }
}
