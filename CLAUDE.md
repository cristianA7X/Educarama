# Educarama — contexto para Claude Code

Plataforma web para que educadores de Scouts de Argentina sigan la progresión personal de los protagonistas. Proyecto universitario en equipo.

Estamos en la rama `refactor/v2`, rehaciendo el proyecto de adentro hacia afuera. El código viejo está en `_legacy/`: se consulta para portar funcionalidad, **nunca se edita ni se incluye desde el código nuevo**.

## Cómo trabajar conmigo

- Una tarea por vez. Antes de cada problema, hacé las preguntas que hagan falta.
- Ante una decisión de diseño, dame el contexto y opciones tipo multiple choice, con una recomendada, para que conteste rápido ("1A, 2B").
- No inventes tablas, columnas ni reglas que no estén en este archivo o en `database/educarama.sql`. Si falta algo, preguntá.
- Código bien comentado, en castellano.
- Al terminar un cambio de arquitectura, agregá una entrada en `docs/explicacion-de-cambios.md`: qué cambió, por qué y cómo funciona.
- Usá siempre los nombres oficiales de Scouts de Argentina (sección "Datos oficiales").

## Stack (cerrado)

PHP 8.1+ con POO, MySQL/MariaDB con PDO, HTML/CSS/JS, Bootstrap 5, todo sobre XAMPP. **No sumes herramientas ni gestores externos** (Composer, npm, frameworks, librerías) sin que te lo pida.

## Estructura

```
Educarama/
├── config/        config.php (BASE_URL, DB_*), config.local.php (gitignored),
│                  bootstrap.php (autoload propio + url()), Database.php
├── database/      educarama.sql (base educarama_v2)
├── docs/          explicacion-de-cambios.md
├── src/
│   ├── Model/     una clase por tabla + Enum/
│   ├── Repository/
│   ├── Service/
│   ├── Controller/
│   └── Helper/
├── public/        index.php, css, fuentes
├── views/         páginas, includes, assets
└── _legacy/       código viejo, solo lectura
```

## Convenciones de código

- Carpetas en inglés, clases en castellano (`ProtagonistaRepository`).
- Cada archivo empieza con `declare(strict_types=1);` y su `namespace`. Namespace = carpeta (`Educarama\Model` → `src/Model/`, `Educarama\Config` → `config/`). Nombre de clase = nombre de archivo, mayúsculas incluidas.
- Autoload propio con `spl_autoload_register` en `config/bootstrap.php`. Cada página o controlador incluye solo `config/bootstrap.php`; nunca `require_once` de clases.
- Links siempre con `url('views/pages/x.php')`; nunca `/Educarama/` escrito a mano.
- Conexión: `Educarama\Config\Database::getConexion()` (PDO singleton, excepciones, FETCH_ASSOC, sin emulación de prepares).
- Base: `snake_case`, singular, `utf8mb4_spanish_ci`. PK `id` propia en entidades; PK compuesta en tablas N:N y de progresión.

### Models (ya hechos: 31 + 5 enums en `src/Model/`)

- `final class` con propiedades públicas promovidas en `camelCase` (columna `id_seccion` → `$idSeccion`).
- `public static function desdeFila(array $fila): self`: único lugar que traduce columnas y convierte tipos (TINYINT → bool, JSON → array, ENUM → enum de PHP).
- `?int $id` es null en un objeto nuevo. Fechas como string `'AAAA-MM-DD'`.
- Sin SQL ni reglas de negocio.
- Enums en `Educarama\Model\Enum`: `EstadoProgresion`, `Elemento`, `EstadoSolicitud`, `Prioridad`, `NivelRol` (int 1–4). Todos con `etiqueta()`.

### Repositories (lo próximo)

- **Siempre devuelven Models** (o arrays de Models), nunca arrays asociativos.
- Una consulta con JOIN para una pantalla devuelve una clase "vista" aparte (ej. `PerfilProtagonista`), no campos extra en un Model.
- Los de tablas estáticas son de solo lectura.
- Consultas preparadas siempre.
- `Usuario` tiene el hash de la contraseña: nunca se pasa entero a una vista.

## Rama vs Sección (lo más importante)

- **Rama** = categoría del programa, estática, 4 filas: Lobatos y Lobeznas, Scouts, Caminantes, Rovers. Tabla `rama`, FK `id_rama`.
- **Sección** = la instancia de un grupo concreto (ej. "Manada Seeonee del grupo 64"). Tabla `seccion`, FK `id_seccion`.

⚠️ En `_legacy/`, `rama`/`id_rama` significaba la **instancia** (hoy sección) y la categoría era `categoria_rama`. Al portar: todo `id_rama` viejo que apunte a la instancia pasa a `id_seccion`; todo `categoria_rama` / `CategoriaRama` pasa a `rama` / `Rama`. Portá cada archivo completo: un `id_rama` sin migrar sigue funcionando pero apunta a otra tabla.

## Datos oficiales

| id | rama | nombre común | especialidades | acciones |
|---|---|---|---|---|
| 1 | Lobatos y Lobeznas | Manada | sí | sí |
| 2 | Scouts | Unidad | sí | sí |
| 3 | Caminantes | Caminantes | no | sí |
| 4 | Rovers | Rovers | no | no |

- Áreas 1 Salud y Bienestar, 2 Paz y Desarrollo, 3 Ambiente, 4 Habilidades para la Vida → LyL, Scouts y Caminantes. Áreas 5–10 (Corporalidad, Creatividad, Carácter, Afectividad, Sociabilidad, Espiritualidad) → solo Rovers.
- Etapas: LyL Pata Tierna, Lobo/a Saltador/a, Rastreador/a, Cazador/a · Scouts **Pistas**, Senda, Rumbo, Travesía · Caminantes 4 etapas por nivel, con el elemento elegido (agua, tierra, fuego, aire) · Rovers Encuentro, Compromiso, Proyección.
- Acción opcional/requerida solo existe en LyL. Rovers tiene competencias pero no acciones. Rovers numera las competencias desde 1 dentro de cada área.
- **Cupo fijo de competencias por etapa** (`etapa.competencias_etapa`, dato estático; lo evalúa el Service). Es el total de la rama dividido por la cantidad de etapas, redondeado; la última etapa se lleva el resto. Adapta el "16 a 20 por etapa" del programa de LyL, imposible con 47 competencias. No hay mínimos, máximos ni porcentajes (Caminantes usa el mismo esquema).

  | Rama | Total | Etapa 1 | Etapa 2 | Etapa 3 | Etapa 4 |
  |---|---|---|---|---|---|
  | Lobatos y Lobeznas | 47 | 12 | 12 | 12 | 11 |
  | Scouts | 53 | 13 | 13 | 13 | 14 |
  | Caminantes | 49 | 12 | 12 | 12 | 13 |
  | Rovers (3 etapas) | 37 | 12 | 12 | 13 | — |
- Catálogo cargado: 186 competencias y 848 acciones.

## Modelo de datos (ver `database/educarama.sql`)

- **Estáticas** (solo devs): `rama`, `area`, `area_rama` (nombre del área en cada rama), `nomenclatura` (términos por rama: `areas`, `competencias`, `pequenio_equipo`), `competencia`, `accion`, `etapa` (`habilitaciones` es solo texto), `tarea_etapa`, `insignia`, `especialidad`, `rol`.
- **Dinámicas**: `grupo`, `seccion`, `patrulla`, `persona`, `protagonista` (no es usuario), `familiar`, `es_familia`, `documento` (subida pendiente), `usuario`, `acceso_grupo`, `acceso_seccion`, `solicitud_alta` (datos en JSON), `evento` + `evento_seccion` (sin filas = todo el grupo).
- **Progresión** (sin fila = pendiente; avanzar de estado = UPDATE): `competencia_protagonista` (con `id_etapa` en que la eligió), `accion_cumplida`, `tarea_etapa_cumplida`, `etapa_lograda` (la actual es la última), `especialidad_protagonista`, `insignia_otorgada` (toda insignia, incluidas las de etapa).

## Reglas de negocio

- **Nada se confirma automáticamente.** Confirman Responsable o Jefe de la sección, o el Jefe del grupo.
- El sistema **avisa** cuando un protagonista alcanza requisitos y **permite saltearlos**: muestra lo adeudado, pide confirmación y registra igual. Lo adeudado se calcula en vivo, no se guarda.
- Al confirmar una etapa, ofrecer otorgar su insignia en la misma operación.
- El Service valida (no la base): la patrulla es de la misma sección; especialidades solo si `rama.usa_especialidades`; acciones solo si `rama.usa_acciones`.
- Alta: el usuario se registra sin accesos → envía `solicitud_alta` → un dev (`usuario.es_admin`) aprueba → se crean grupo y secciones y queda como Jefe del grupo. Cruz del Sur: interfaz `ValidadorCruzDelSur` que hoy devuelve siempre "pendiente".
- Roles (`NivelRol`): Supervisor 1 (estadísticas, sin datos personales), Ayudante 2 (lectura), Responsable 3 (crear/modificar y confirmar logros), Jefe 4 (eliminar y gestionar roles). En sección, el Jefe solo cambia el rol de usuarios ya asignados. El Jefe de grupo es Jefe de todas sus secciones **por derivación en código**, sin filas en `acceso_seccion`.
- Herencia de accesos: **solo** el Jefe de grupo hereda el rol Jefe en todas las secciones de su grupo. Los demás roles de grupo (Supervisor, Ayudante, Responsable) valen solo para las pantallas del grupo (estadísticas agregadas, listado de secciones, eventos grupales) y no dan acceso al detalle de ninguna sección: para eso hace falta una fila en `acceso_seccion`. Si un usuario tiene rol de grupo y de sección, en la sección vale el de sección (salvo que sea Jefe de grupo, que siempre es Jefe).
- Admin (`usuario.es_admin`): solo aprueba o rechaza solicitudes de alta. No ve datos de ningún grupo por ser admin; si necesita entrar a uno, se le da un acceso como a cualquier usuario.

## Bugs conocidos del legacy (no repetirlos al portar)

- `perfil_protagonista.php` deduce la rama con `strpos` sobre el nombre de la sección → usar `id_rama` por JOIN (sección → rama), también para la clase CSS `rama-xxx`.
- `perfil_rama.php`: `$rama_page` sin definir y `$rama['descripcion']` inexistente. Pasa a ser perfil de sección.
- `CategoriaRamaController/Repository` permiten escribir una tabla estática.
- `CompetenciaRepository` usa la tabla inexistente `protagonista_accion`, y `verificarAccionesCompletas` no filtra por competencia.
- `perfil_protagonista.php` construye `ProgresionService` con argumentos que no recibe.
- `FamiliarService` llama `PersonaRepository::agregar`, que no existe.
- `alta_protagonista.php` calcula `$esEdicion` antes de cargar `$protagonista`.
- `patrullas.php`: "Crear nueva patrulla" apunta a `alta_protagonista.php`. `perfil_patrulla.php` enlaza a `asignar_protagonista.php`, que no existe.
- `gestionar_competencias.php` imprime HTML antes del `<!DOCTYPE>`; hay que rehacerla (pestañas por área con checkboxes).
- Insignias de LyL hardcodeadas e imágenes con espacios en el nombre.

## Para más adelante (no implementar todavía)

Baja lógica con respaldo de 1 mes · integración real con Cruz del Sur · plantillas de evento (`id_tipo_evento` reservado) · acciones personalizadas de protagonistas · momentos de la progresión · rutas de imágenes de insignias · auditoría de confirmaciones · habilitaciones que bloqueen · generador del catálogo desde CSV (reservado fuera del repo).

## Orden de trabajo

1. ~~Diagrama ER, `educarama.sql`, config, autoload y Models~~ ✅
2. Repositories (empezando por los de tablas estáticas).
3. Services.
4. Controllers.
5. Views, empezando por `gestionar_competencias.php` y el perfil del protagonista.
