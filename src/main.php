<?php
// src/main.php
declare(strict_types=1);

namespace Educarama;

require_once __DIR__ . '/../config/bootstrap.php';

use Educarama\Config\Database;
use Educarama\Contenedor;

// Repositorios
use Educarama\Repository\AccesoGrupoRepository;
use Educarama\Repository\AccesoSeccionRepository;
use Educarama\Repository\AccionCumplidaRepository;
use Educarama\Repository\AccionRepository;
use Educarama\Repository\AreaRamaRepository;
use Educarama\Repository\AreaRepository;
use Educarama\Repository\CompetenciaProtagonistaRepository;
use Educarama\Repository\CompetenciaRepository;
use Educarama\Repository\DocumentoRepository;
use Educarama\Repository\EsFamiliaRepository;
use Educarama\Repository\EspecialidadProtagonistaRepository;
use Educarama\Repository\EspecialidadRepository;
use Educarama\Repository\EtapaLogradaRepository;
use Educarama\Repository\EtapaRepository;
use Educarama\Repository\EventoRepository;
use Educarama\Repository\EventoSeccionRepository;
use Educarama\Repository\FamiliarRepository;
use Educarama\Repository\GrupoRepository;
use Educarama\Repository\InsigniaOtorgadaRepository;
use Educarama\Repository\InsigniaRepository;
use Educarama\Repository\NomenclaturaRepository;
use Educarama\Repository\PatrullaRepository;
use Educarama\Repository\PersonaRepository;
use Educarama\Repository\ProtagonistaRepository;
use Educarama\Repository\RamaRepository;
use Educarama\Repository\RolRepository;
use Educarama\Repository\SeccionRepository;
use Educarama\Repository\SolicitudAltaRepository;
use Educarama\Repository\TareaEtapaCumplidaRepository;
use Educarama\Repository\TareaEtapaRepository;
use Educarama\Repository\UsuarioRepository;

// Servicios
use Educarama\Service\AccesoService;
use Educarama\Service\DocumentoService;
use Educarama\Service\EventoService;
use Educarama\Service\FamiliarService;
use Educarama\Service\PatrullaService;
use Educarama\Service\PermisoService;
use Educarama\Service\ProgresionService;
use Educarama\Service\ProtagonistaService;
use Educarama\Service\SeccionService;
use Educarama\Service\SolicitudAltaService;
use Educarama\Service\UsuarioService;

// Controladores
use Educarama\Controller\AccesoController;
use Educarama\Controller\DocumentoController;
use Educarama\Controller\EventoController;
use Educarama\Controller\FamiliarController;
use Educarama\Controller\PatrullaController;
use Educarama\Controller\ProgresionController;
use Educarama\Controller\ProtagonistaController;
use Educarama\Controller\SolicitudAltaController;
use Educarama\Controller\UsuarioController;
use Educarama\Controller\InicioController;
use Educarama\Controller\MenuController;
use Educarama\Controller\SeccionController;

$c = new Contenedor();

// 1. Base de Datos
$c->registrar(\PDO::class, fn() => Database::getConexion());

// 2. Repositorios
$c->registrar(AccesoGrupoRepository::class, fn($c) => new AccesoGrupoRepository($c->obtener(\PDO::class)));
$c->registrar(AccesoSeccionRepository::class, fn($c) => new AccesoSeccionRepository($c->obtener(\PDO::class)));
$c->registrar(AccionCumplidaRepository::class, fn($c) => new AccionCumplidaRepository($c->obtener(\PDO::class)));
$c->registrar(AccionRepository::class, fn($c) => new AccionRepository($c->obtener(\PDO::class)));
$c->registrar(AreaRamaRepository::class, fn($c) => new AreaRamaRepository($c->obtener(\PDO::class)));
$c->registrar(AreaRepository::class, fn($c) => new AreaRepository($c->obtener(\PDO::class)));
$c->registrar(CompetenciaProtagonistaRepository::class, fn($c) => new CompetenciaProtagonistaRepository($c->obtener(\PDO::class)));
$c->registrar(CompetenciaRepository::class, fn($c) => new CompetenciaRepository($c->obtener(\PDO::class)));
$c->registrar(DocumentoRepository::class, fn($c) => new DocumentoRepository($c->obtener(\PDO::class)));
$c->registrar(EsFamiliaRepository::class, fn($c) => new EsFamiliaRepository($c->obtener(\PDO::class)));
$c->registrar(EspecialidadProtagonistaRepository::class, fn($c) => new EspecialidadProtagonistaRepository($c->obtener(\PDO::class)));
$c->registrar(EspecialidadRepository::class, fn($c) => new EspecialidadRepository($c->obtener(\PDO::class)));
$c->registrar(EtapaLogradaRepository::class, fn($c) => new EtapaLogradaRepository($c->obtener(\PDO::class)));
$c->registrar(EtapaRepository::class, fn($c) => new EtapaRepository($c->obtener(\PDO::class)));
$c->registrar(EventoRepository::class, fn($c) => new EventoRepository($c->obtener(\PDO::class)));
$c->registrar(EventoSeccionRepository::class, fn($c) => new EventoSeccionRepository($c->obtener(\PDO::class)));
$c->registrar(FamiliarRepository::class, fn($c) => new FamiliarRepository($c->obtener(\PDO::class)));
$c->registrar(GrupoRepository::class, fn($c) => new GrupoRepository($c->obtener(\PDO::class)));
$c->registrar(InsigniaOtorgadaRepository::class, fn($c) => new InsigniaOtorgadaRepository($c->obtener(\PDO::class)));
$c->registrar(InsigniaRepository::class, fn($c) => new InsigniaRepository($c->obtener(\PDO::class)));
$c->registrar(NomenclaturaRepository::class, fn($c) => new NomenclaturaRepository($c->obtener(\PDO::class)));
$c->registrar(PatrullaRepository::class, fn($c) => new PatrullaRepository($c->obtener(\PDO::class)));
$c->registrar(PersonaRepository::class, fn($c) => new PersonaRepository($c->obtener(\PDO::class)));
$c->registrar(ProtagonistaRepository::class, fn($c) => new ProtagonistaRepository($c->obtener(\PDO::class)));
$c->registrar(RamaRepository::class, fn($c) => new RamaRepository($c->obtener(\PDO::class)));
$c->registrar(RolRepository::class, fn($c) => new RolRepository($c->obtener(\PDO::class)));
$c->registrar(SeccionRepository::class, fn($c) => new SeccionRepository($c->obtener(\PDO::class)));
$c->registrar(SolicitudAltaRepository::class, fn($c) => new SolicitudAltaRepository($c->obtener(\PDO::class)));
$c->registrar(TareaEtapaCumplidaRepository::class, fn($c) => new TareaEtapaCumplidaRepository($c->obtener(\PDO::class)));
$c->registrar(TareaEtapaRepository::class, fn($c) => new TareaEtapaRepository($c->obtener(\PDO::class)));
$c->registrar(UsuarioRepository::class, fn($c) => new UsuarioRepository($c->obtener(\PDO::class)));

// 3. Servicios
$c->registrar(UsuarioService::class, fn($c) => new UsuarioService(
    $c->obtener(\PDO::class),
    $c->obtener(UsuarioRepository::class)
));

$c->registrar(PermisoService::class, fn($c) => new PermisoService(
    $c->obtener(\PDO::class),
    $c->obtener(AccesoGrupoRepository::class),
    $c->obtener(AccesoSeccionRepository::class),
    $c->obtener(RolRepository::class),
    $c->obtener(SeccionRepository::class),
    $c->obtener(UsuarioRepository::class)
));

$c->registrar(SeccionService::class, fn($c) => new SeccionService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(SeccionRepository::class),
    $c->obtener(GrupoRepository::class),
    $c->obtener(RamaRepository::class),
    $c->obtener(NomenclaturaRepository::class),
    $c->obtener(AccesoGrupoRepository::class),
    $c->obtener(AccesoSeccionRepository::class),
    $c->obtener(ProtagonistaRepository::class),
    $c->obtener(PatrullaRepository::class)
));

$c->registrar(PatrullaService::class, fn($c) => new PatrullaService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(PatrullaRepository::class),
    $c->obtener(ProtagonistaRepository::class),
    $c->obtener(SeccionRepository::class),
    $c->obtener(NomenclaturaRepository::class)
));

$c->registrar(ProtagonistaService::class, fn($c) => new ProtagonistaService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(PatrullaService::class),
    $c->obtener(ProtagonistaRepository::class),
    $c->obtener(PersonaRepository::class),
    $c->obtener(SeccionRepository::class),
    new \Educarama\Almacen\AlmacenArchivos(),
    $c->obtener(SeccionService::class)
));

$c->registrar(AccesoService::class, fn($c) => new AccesoService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(AccesoGrupoRepository::class),
    $c->obtener(AccesoSeccionRepository::class),
    $c->obtener(UsuarioRepository::class),
    $c->obtener(GrupoRepository::class),
    $c->obtener(SeccionRepository::class),
    $c->obtener(RolRepository::class)
));

$c->registrar(DocumentoService::class, fn($c) => new DocumentoService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(ProtagonistaRepository::class),
    $c->obtener(DocumentoRepository::class),
    new \Educarama\Almacen\AlmacenArchivos()
));

$c->registrar(EventoService::class, fn($c) => new EventoService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(EventoRepository::class),
    $c->obtener(EventoSeccionRepository::class),
    $c->obtener(GrupoRepository::class),
    $c->obtener(SeccionRepository::class),
    $c->obtener(SeccionService::class)
));

$c->registrar(FamiliarService::class, fn($c) => new FamiliarService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(ProtagonistaRepository::class),
    $c->obtener(PersonaRepository::class),
    $c->obtener(FamiliarRepository::class),
    $c->obtener(EsFamiliaRepository::class)
));

$c->registrar(ProgresionService::class, fn($c) => new ProgresionService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(ProtagonistaRepository::class),
    $c->obtener(SeccionRepository::class),
    $c->obtener(RamaRepository::class),
    $c->obtener(EtapaRepository::class),
    $c->obtener(CompetenciaRepository::class),
    $c->obtener(AccionRepository::class),
    $c->obtener(TareaEtapaRepository::class),
    $c->obtener(InsigniaRepository::class),
    $c->obtener(EspecialidadRepository::class),
    $c->obtener(AreaRamaRepository::class),
    $c->obtener(CompetenciaProtagonistaRepository::class),
    $c->obtener(AccionCumplidaRepository::class),
    $c->obtener(TareaEtapaCumplidaRepository::class),
    $c->obtener(EtapaLogradaRepository::class),
    $c->obtener(EspecialidadProtagonistaRepository::class),
    $c->obtener(InsigniaOtorgadaRepository::class),
    $c->obtener(NomenclaturaRepository::class),
    $c->obtener(AreaRepository::class)
));

$c->registrar(SolicitudAltaService::class, fn($c) => new SolicitudAltaService(
    $c->obtener(\PDO::class),
    $c->obtener(PermisoService::class),
    $c->obtener(SolicitudAltaRepository::class),
    $c->obtener(GrupoRepository::class),
    $c->obtener(SeccionRepository::class),
    $c->obtener(AccesoGrupoRepository::class),
    $c->obtener(RolRepository::class),
    $c->obtener(RamaRepository::class),
    $c->obtener(UsuarioRepository::class)
));

// 4. Controladores
$c->registrar(UsuarioController::class, fn($c) => new UsuarioController(
    $c->obtener(UsuarioService::class),
    $c->obtener(SeccionService::class)
));

$c->registrar(InicioController::class, fn($c) => new InicioController(
    $c->obtener(UsuarioService::class),
    $c->obtener(SeccionService::class),
    $c->obtener(EventoService::class),
    $c->obtener(SolicitudAltaService::class)
));

$c->registrar(MenuController::class, fn($c) => new MenuController(
    $c->obtener(UsuarioService::class),
    $c->obtener(SeccionService::class)
));

$c->registrar(SeccionController::class, fn($c) => new SeccionController(
    $c->obtener(UsuarioService::class),
    $c->obtener(SeccionService::class),
    $c->obtener(EventoService::class)
));

$c->registrar(AccesoController::class, fn($c) => new AccesoController(
    $c->obtener(UsuarioService::class),
    $c->obtener(AccesoService::class),
    $c->obtener(SeccionService::class)
));

$c->registrar(DocumentoController::class, fn($c) => new DocumentoController(
    $c->obtener(UsuarioService::class),
    $c->obtener(DocumentoService::class)
));

$c->registrar(EventoController::class, fn($c) => new EventoController(
    $c->obtener(UsuarioService::class),
    $c->obtener(EventoService::class),
    $c->obtener(SeccionService::class)
));

$c->registrar(FamiliarController::class, fn($c) => new FamiliarController(
    $c->obtener(UsuarioService::class),
    $c->obtener(FamiliarService::class),
    $c->obtener(ProtagonistaService::class),
    $c->obtener(SeccionService::class)
));

$c->registrar(PatrullaController::class, fn($c) => new PatrullaController(
    $c->obtener(UsuarioService::class),
    $c->obtener(PatrullaService::class),
    $c->obtener(ProtagonistaService::class),
    $c->obtener(SeccionService::class)
));

$c->registrar(ProgresionController::class, fn($c) => new ProgresionController(
    $c->obtener(UsuarioService::class),
    $c->obtener(ProgresionService::class),
    $c->obtener(ProtagonistaService::class)
));

$c->registrar(ProtagonistaController::class, fn($c) => new ProtagonistaController(
    $c->obtener(UsuarioService::class),
    $c->obtener(ProtagonistaService::class),
    $c->obtener(SeccionService::class),
    $c->obtener(PatrullaService::class),
    $c->obtener(ProgresionService::class),
    $c->obtener(FamiliarService::class),
    $c->obtener(DocumentoService::class)
));

$c->registrar(SolicitudAltaController::class, fn($c) => new SolicitudAltaController(
    $c->obtener(UsuarioService::class),
    $c->obtener(SolicitudAltaService::class)
));

return $c;