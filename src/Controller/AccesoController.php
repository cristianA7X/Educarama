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

final class AccesoController extends Controlador
{
    public function __construct(
        UsuarioService $usuarioService,
        private readonly AccesoService $accesos,
        private readonly SeccionService $secciones
    ) {
        parent::__construct($usuarioService);
    }

    public function paginaListado(array $get): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        $idSeccion = self::enteroOpcional($get, 'id_seccion');
        if ($idSeccion !== null) {
            $pantalla = $this->accesos->pantallaSeccion($idUsuario, $idSeccion);
            return Respuesta::pagina([
                'modo'     => 'seccion',
                'seccion'  => $this->secciones->encabezado($idUsuario, $idSeccion),
                'pantalla' => $pantalla,
            ]);
        }
        $idGrupo = self::entero($get, 'id_grupo');
        $pantalla = $this->accesos->pantallaGrupo($idUsuario, $idGrupo);
        return Respuesta::pagina([
            'modo'              => 'grupo',
            'grupo'             => $this->secciones->grupo($idUsuario, $idGrupo),
            'pantalla'          => $pantalla,
            'accesosSecciones'  => $this->accesos->accesosPorSeccionDeGrupo($idUsuario, $idGrupo),
        ]);
    }

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
        
        if ($ambito === 'grupo' && $idDestino === $idUsuario && $nivel !== NivelRol::Jefe) {
            return Respuesta::exito(redireccion: url('views/pages/grupo.php?id=' . $id));
        }
        return Respuesta::exito(redireccion: self::lista($ambito, $id));
    }

    public function quitar(array $post): Respuesta
    {
        $idUsuario = $this->usuario()->id;
        [$ambito, $id] = self::ambito($post);
        $idDestino = self::entero($post, 'id_usuario');
        $ambito === 'grupo'
            ? $this->accesos->quitarAccesoGrupo($idUsuario, $id, $idDestino)
            : $this->accesos->quitarAccesoSeccion($idUsuario, $id, $idDestino);
        Flash::guardar(Flash::EXITO, 'Acceso quitado.');
        
        if ($ambito === 'grupo' && $idDestino === $idUsuario) {
            return Respuesta::exito(redireccion: url('views/pages/inicio.php'));
        }
        return Respuesta::exito(redireccion: self::lista($ambito, $id));
    }

    private static function ambito(array $post): array
    {
        return match (self::texto($post, 'ambito')) {
            'grupo'   => ['grupo', self::entero($post, 'id_grupo')],
            'seccion' => ['seccion', self::entero($post, 'id_seccion')],
            default   => throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.'),
        };
    }

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