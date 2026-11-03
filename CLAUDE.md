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
├── config/        config.php (BASE_URL, DB_*, ALMACEN_*), config.local.php (gitignored),
│                  bootstrap.php (autoload propio + url()), Database.php
├── database/      educarama.sql (base educarama_v2)
├── docs/          explicacion-de-cambios.md
├── src/
│   ├── Almacen/   componente genérico de archivos subidos (no depende de nada del proyecto)
│   ├── Model/     una clase por tabla + Enum/
│   ├── Repository/  + Vista/ (clases vista de consultas con JOIN)
│   ├── Service/     + Vista/ (avisos y resúmenes) + Excepcion/
│   ├── Controller/
│   └── Helper/    Csrf
├── public/        index.php, css, fuentes
├── storage/       archivos subidos (gitignored salvo su .htaccess "Require all denied")
├── views/         páginas, includes, assets
└── _legacy/       código viejo, solo lectura
```

### Archivos subidos: dos capas

1. **`src/Almacen/` (`Educarama\Almacen`)**: componente genérico y reutilizable en otros proyectos. **No depende de ninguna otra clase del proyecto** (ni Models, ni Repositories, ni Services, ni la base): solo PHP y las constantes `ALMACEN_*`. Guarda (valida tamaño y tipo real con `finfo`, nombre aleatorio), envía (headers seguros) y borra. No sabe qué es un protagonista. No agregarle nada específico de Educarama.
2. **El Service de cada caso** (ej. `DocumentoService`): permisos, pertenencia, tabla y en qué subcarpeta va cada cosa. Traduce `ArchivoInvalidoException` a `ReglaNegocioException`.

Nunca un link directo a un archivo: la descarga pasa por un Controller que llama al Service (permisos) y después a `AlmacenArchivos::enviar()`.

### Formularios POST

- **Todo formulario POST usa `Educarama\Helper\Csrf`**: `<?= Csrf::campo() ?>` en la vista y `Csrf::esValido($_POST[Csrf::CAMPO] ?? null)` en el Controller antes de procesar.
- Los mensajes de error al usuario nunca muestran rutas del disco ni detalles internos: solo los de `ServicioException`.

### Sesión

- La sesión se arranca siempre con `Educarama\Helper\Sesion::iniciar()` (cookie httponly, SameSite=Lax, `use_strict_mode`), nunca con `session_start()` suelto.
- **`UsuarioService` es el único Service que toca `$_SESSION`** (login, logout, usuario actual). Guarda solo el id del usuario; los datos se releen de la base. Los demás Services reciben `$idUsuario` como parámetro: el Controller lo obtiene con `$usuarioService->exigirSesion()->id`.
- Al loguearse se renuevan el id de sesión y el token CSRF.

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

### Repositories (hechos)

- **Siempre devuelven Models** (o arrays de Models), nunca arrays asociativos.
- Una consulta con JOIN para una pantalla devuelve una clase "vista" aparte en `src/Repository/Vista/` (ej. `FichaProtagonista`), no campos extra en un Model.
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
- **Dinámicas**: `grupo`, `seccion`, `patrulla`, `persona`, `protagonista` (no es usuario), `familiar`, `es_familia`, `documento` (el archivo vive en el almacén; la fila guarda ruta relativa, nombre original, tipo MIME y tamaño), `usuario`, `acceso_grupo`, `acceso_seccion`, `solicitud_alta` (datos en JSON), `evento` + `evento_seccion` (sin filas = todo el grupo).
- **Progresión** (sin fila = pendiente; avanzar de estado = UPDATE): `competencia_protagonista` (con `id_etapa` en que la eligió), `accion_cumplida`, `tarea_etapa_cumplida`, `etapa_lograda` (la actual es la última), `especialidad_protagonista`, `insignia_otorgada` (toda insignia, incluidas las de etapa).

## Reglas de negocio

- **Nada se confirma automáticamente.** Confirman Responsable o Jefe de la sección, o el Jefe del grupo.
- El sistema **avisa** cuando un protagonista alcanza requisitos y **permite saltearlos**: muestra lo adeudado, pide confirmación y registra igual. Lo adeudado se calcula en vivo, no se guarda.
- El Service valida (no la base): la patrulla es de la misma sección; especialidades solo si `rama.usa_especialidades`; acciones solo si `rama.usa_acciones`.
- Alta: el usuario se registra sin accesos → envía `solicitud_alta` → un dev (`usuario.es_admin`) aprueba → se crean grupo y secciones y queda como Jefe del grupo. La validación con Cruz del Sur queda para más adelante (`email_validado` siempre en false).
  - `datos_grupo` = `{codigo, nombre}`; `datos_secciones` = lista de `{nombre, id_rama}`. Se valida al enviar y otra vez al aprobar: código no usado, al menos una sección, ramas válidas, sin nombres de sección repetidos.
  - Una sola solicitud pendiente por usuario; si se rechaza, puede enviar otra. No se edita.
  - Rechazar exige `motivo_rechazo`, que ve el solicitante. (El aviso por email queda para más adelante.)
- Cuentas: email único (se guarda en minúsculas). Login con mensaje único "Email o contraseña incorrectos." (no revela si el email existe). Contraseña: **versión B activa** (mínimo 6, para las pruebas); la **versión A** (mínimo 8, máximo 72 bytes) está comentada en `UsuarioService::validarContrasenia()` lista para producción.
- Roles (`NivelRol`): Supervisor 1 (estadísticas, sin datos personales), Ayudante 2 (lectura), Responsable 3 (crear/modificar y confirmar logros), Jefe 4 (eliminar y gestionar roles). En sección, el Jefe solo cambia el rol de usuarios ya asignados. El Jefe de grupo es Jefe de todas sus secciones **por derivación en código**, sin filas en `acceso_seccion`.
- Herencia de accesos: **solo** el Jefe de grupo hereda el rol Jefe en todas las secciones de su grupo. Los demás roles de grupo (Supervisor, Ayudante, Responsable) valen solo para las pantallas del grupo (estadísticas agregadas, listado de secciones, eventos grupales) y no dan acceso al detalle de ninguna sección: para eso hace falta una fila en `acceso_seccion`. Si un usuario tiene rol de grupo y de sección, en la sección vale el de sección (salvo que sea Jefe de grupo, que siempre es Jefe).
- Admin (`usuario.es_admin`): solo aprueba o rechaza solicitudes de alta. No ve datos de ningún grupo por ser admin; si necesita entrar a uno, se le da un acceso como a cualquier usuario.

### Progresión

- **Etapa actual** = la última lograda (`etapa_lograda` es la etapa **completada**). **Etapa en curso** = la siguiente a la actual, o la primera de la rama si no logró ninguna. Al elegir una competencia (pasarla a En proceso), su `id_etapa` toma la etapa en curso.
- **Cupo:** se cuentan las competencias con `id_etapa` igual a la etapa y se comparan con su `competencias_etapa`. Dos avisos, los dos se pueden saltear:
  1. Al elegir: si en proceso + desarrolladas de la etapa en curso ya llegan al cupo, se avisa que está eligiendo de más; si el educador confirma, se registra igual.
  2. Al completar: cuando las desarrolladas de la etapa en curso llegan al cupo, se sugiere confirmar la etapa.
- **Competencia desarrollada:** LyL requiere todas sus acciones requeridas (`es_opcional = 0`); Scouts y Caminantes, todas sus acciones; Rovers, nada. Si falta algo, se avisa y se puede saltear. Al cumplir la última acción, se sugiere "¿dar la competencia por desarrollada?".
- **Confirmar una etapa:** requiere el cupo de competencias desarrolladas y todas sus tareas (`tarea_etapa`); las dos cosas se pueden saltear mostrando lo adeudado. Lo que **bloquea** (no se saltea) es que la etapa anterior no esté lograda. En Caminantes el elemento es **obligatorio**; en las demás ramas el Service lo rechaza.
- **Quién deshace:** destildar acciones y volver atrás el estado de una competencia, Responsable en adelante. Quitar una etapa lograda o una insignia otorgada, solo Jefe.
- **Casos borde:** si ya logró todas las etapas, una competencia nueva se asigna a la última etapa de la rama, con aviso de cupo excedido. Solo se puede quitar la **última** etapa lograda. Retroceder una competencia a Pendiente conserva sus acciones cumplidas. No se pueden tildar acciones de una competencia Pendiente: primero hay que elegirla.
- **Insignias: registro aparte y manual.** No están atadas a confirmar una etapa. Se registran a mano en un submenú propio del protagonista, con su propia fecha (la de la entrega física), en `insignia_otorgada`. Según el programa, la insignia de una etapa se entrega cuando la etapa empieza: apenas una etapa pasa a estar en curso, si el protagonista no tiene su insignia se avisa "tiene la etapa X en curso y todavía no recibió su insignia". Nada automático. `etapa_lograda` no tiene relación funcional con la insignia.

### Protagonistas y patrullas

- Obligatorios: DNI (entero positivo), nombre y apellido. Fecha de nacimiento opcional. Año de ingreso opcional y no futuro.
- El DNI es único en `persona`: si ya existe, el alta se rechaza, aunque sea de un familiar.
- Pase de sección: método aparte, solo dentro del mismo grupo, con Responsable en las dos secciones. Queda sin patrulla; la progresión de la rama anterior queda como historial.
- Eliminar un protagonista (Jefe) borra en cascada su progresión, documentos y vínculos; su `persona` queda.
- Patrullas: el nombre no se repite dentro de la sección. Crear, renombrar y asignar integrantes, Responsable; eliminar, Jefe (los integrantes quedan sin patrulla).

### Familiares

- Se gestionan siempre desde un protagonista: permiso en su sección y el familiar tiene que estar vinculado a él. Leer, Ayudante; alta, edición y desvincular, Responsable.
- Obligatorios: DNI, nombre y apellido. Teléfono (texto libre) y email (validado) opcionales. Contactos de emergencia libres (varios o ninguno); los listados los muestran primero.
- Alta con un DNI ya cargado: aviso con solo el nombre (nunca a quién más está vinculado) y, si se confirma, solo se crea el vínculo; no se pisan datos guardados. Si la persona existe pero no es familiar (ej. un hermano Rover), se crea su ficha de familiar sobre esa persona.
- Editar un familiar compartido cambia sus datos para todos sus protagonistas; el vínculo (`es_familia`) es de cada protagonista.
- Desvincular: si queda sin protagonistas, aviso con su nombre y, solo si se confirma, se borra su ficha de `familiar` (la `persona` queda).

### Documentos

- Ver y descargar, Ayudante; subir y editar tipo/descripción, Responsable; eliminar, Jefe.
- PDF, JPG y PNG hasta 5 MB (`ALMACEN_*`). `tipo_documento` texto libre opcional. En disco: `documentos/protagonista/{id}/`.
- Pertenencia: documento → protagonista → sección → acceso. Sin acceso a la sección, **404** (no se confirma que existe); con acceso pero sin el rol, 403.
- Subir: primero el archivo, después la fila (si la fila falla, se borra el archivo). Eliminar: primero la fila, después el archivo. Eliminar un protagonista borra también su carpeta.
- PDF siempre como adjunto; imágenes pueden mostrarse en línea.

### Pantalla de competencias del protagonista

- Una pestaña por área, con todas las competencias de la rama aunque no tengan fila (sin fila = pendiente).
- Cada competencia tiene un botón que **solo avanza**: Pendiente → En proceso (la elige para la etapa en curso; aviso de cupo) → Desarrollada (aviso de acciones).
- **Volver atrás:** botón ⋮ arriba de las pestañas, al lado del filtro, con la opción "Corregir". Activa un modo que permite retroceder (Desarrollada → En proceso → Pendiente, que borra la fila). Solo Responsable en adelante.
- **Acciones:** casillas sí/no dentro de cada competencia, en una sección desplegable. Rovers no las muestra.
- **Contadores:** "Etapa en curso: X/cupo" y "Total: X/total de la rama" arriba; en cada pestaña, las desarrolladas de esa área.
- **Filtro** arriba de las pestañas: Todas / Pendientes / En proceso / Desarrolladas.
- **Guardado inmediato:** cada clic se guarda en el momento por fetch, con operaciones de a una (no hay guardado por lote). Si hay un aviso (cupo excedido, acciones sin cumplir), primero se muestra la confirmación y después se guarda.
- `ProgresionService::resumen()` (Ayudante en adelante) devuelve una clase vista con: etapa actual, etapa en curso, contadores (etapa, total y por área), lo adeudado para confirmar la etapa en curso y el aviso de insignia pendiente.

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

Baja lógica con respaldo de 1 mes · integración con Cruz del Sur (`ValidadorCruzDelSur`, resultado al revisar solicitudes, `email_validado`) · envío de emails (aviso de solicitud rechazada: `SolicitudAltaService::notificarRechazo`) · recuperación de contraseña (`token_recuperacion` reservado; necesita envío de emails) · bloqueo por intentos de login fallidos · activar la versión A de contraseña · plantillas de evento (`id_tipo_evento` reservado) · acciones personalizadas de protagonistas · momentos de la progresión · rutas de imágenes de insignias · auditoría de confirmaciones · habilitaciones que bloqueen · generador del catálogo desde CSV (reservado fuera del repo).

## Orden de trabajo

1. ~~Diagrama ER, `educarama.sql`, config, autoload y Models~~ ✅
2. Repositories (empezando por los de tablas estáticas).
3. Services.
4. Controllers.
5. Views, empezando por `gestionar_competencias.php` y el perfil del protagonista.
