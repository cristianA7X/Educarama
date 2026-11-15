# Educarama — contexto para Claude Code

Plataforma web para que educadores de Scouts de Argentina sigan la progresión personal de los protagonistas. Proyecto universitario en equipo.

Estamos en la rama `refactor/v2.1` (la v2 más la inyección de dependencias con Composition Root), rehaciendo el proyecto de adentro hacia afuera. El código viejo está en `_legacy/`: se consulta para portar funcionalidad, **nunca se edita ni se incluye desde el código nuevo**.

## Cómo trabajar conmigo

- Una tarea por vez. Antes de cada problema, hacé las preguntas que hagan falta.
- Ante una decisión de diseño, dame el contexto y opciones tipo multiple choice, con una recomendada, para que conteste rápido ("1A, 2B").
- No inventes tablas, columnas ni reglas que no estén en este archivo o en `database/educarama.sql`. Si falta algo, preguntá.
- Código bien comentado, en castellano.
- Al terminar un cambio de arquitectura, agregá una entrada en `docs/explicacion-de-cambios.md`: qué cambió, por qué y cómo funciona.
- Usá siempre los nombres oficiales de Scouts de Argentina (sección "Datos oficiales").

## Stack (cerrado)

PHP 8.1+ con POO, MySQL/MariaDB con PDO, HTML/CSS/JS, Bootstrap 5, todo sobre XAMPP. **No sumes herramientas ni gestores externos** (Composer, npm, frameworks, librerías) sin que te lo pida.

Librerías de front ya aprobadas, todas por CDN con versión fija (en `views/includes/cabecera.php` y `pie.php`): Bootstrap 5.3.3, Bootstrap Icons 1.10.5 y SweetAlert2 11 (confirmaciones y avisos; decisión del equipo, mantenida del legacy). No sumar otras.

## Estructura

```
Educarama/
├── config/        config.php (BASE_URL, ZONA_HORARIA, DB_*, ALMACEN_*), config.local.php (gitignored),
│                  bootstrap.php (autoload propio + url()), Database.php
├── database/      educarama.sql (base educarama_v2), seeds/catalogo_competencias.sql,
│                  datos_ejemplo.sql (solo desarrollo: usuarios de prueba y progresión variada)
├── docs/          descripcion-tecnica.md, fundamentos-y-decisiones.md, explicacion-de-cambios.md,
│                  prompt-imagen-de-marca.md (plan de marca T1–T7, hecho)
├── src/
│   ├── main.php   Composition Root: registra en el contenedor PDO, Repositories, Services y Controllers
│   ├── Contenedor.php  contenedor de dependencias (registrar / obtener, una instancia por pedido)
│   ├── Almacen/   componente genérico de archivos subidos (no depende de nada del proyecto)
│   ├── Model/     una clase por tabla + Enum/
│   ├── Repository/  + Vista/ (clases vista de consultas con JOIN)
│   ├── Service/     + Vista/ (avisos y resúmenes) + Excepcion/
│   ├── Controller/  Controlador (base), Respuesta, Api, Pagina + un Controller por tema
│   └── Helper/    Csrf, Sesion, Flash, Vista (ayudas de presentación)
├── public/        api.php (todos los fetch), descargar.php (documentos), index.php,
│                  css/styles.css, js/ (educarama.js + uno por pantalla compleja),
│                  fonts/ubuntu (woff2) y fonts/Ubuntu_Condensed (TTF)
├── storage/       archivos subidos (gitignored salvo su .htaccess "Require all denied")
├── views/         pages/ (una por pantalla), includes/ (cabecera, pie, menu, error),
│                  assets/images: Iconos/ (18 pictogramas oficiales), Insignias/Progresion/,
│                  Logos/ (Logos_Educarama, Logos_AreasYRamas, logotipos SAAC y WOSM)
└── _legacy/       código viejo, solo lectura
```

### Datos de ejemplo

`database/datos_ejemplo.sql` es **solo para desarrollo**: borra los datos dinámicos y de progresión, reinicia sus AUTO_INCREMENT y carga dos grupos, 60 protagonistas, familiares, eventos y un usuario por caso de permisos (contraseña `educarama123`). No toca las tablas estáticas. Se importa después de `educarama.sql` y del catálogo. **Si cambia el esquema, hay que mantenerlo al día** y verificar que importa sin errores sobre una base recién creada. Las acciones, tareas, insignias y especialidades se referencian por su clave natural, no por id.

### Archivos subidos: dos capas

1. **`src/Almacen/` (`Educarama\Almacen`)**: componente genérico y reutilizable en otros proyectos. **No depende de ninguna otra clase del proyecto** (ni Models, ni Repositories, ni Services, ni la base): solo PHP y las constantes `ALMACEN_*`. Guarda (valida tamaño y tipo real con `finfo`, nombre aleatorio), envía (headers seguros) y borra. No sabe qué es un protagonista. No agregarle nada específico de Educarama.
2. **El Service de cada caso** (ej. `DocumentoService`): permisos, pertenencia, tabla y en qué subcarpeta va cada cosa. Traduce `ArchivoInvalidoException` a `ReglaNegocioException`.

Nunca un link directo a un archivo: la descarga pasa por un Controller que llama al Service (permisos) y después a `AlmacenArchivos::enviar()`. Es `public/descargar.php?id=` (GET; `&ver=1` muestra imágenes en línea); un archivo que falta en el disco es 404 "El archivo no está disponible.".

### Controllers y pedidos

- **Páginas** (`views/pages/*.php`): solo GET, para mostrar. Empiezan con `$app = require_once __DIR__ . '/../../src/main.php';` y `$datos = Pagina::cargar(fn () => $app->obtener(XController::class)->paginaY($_GET));`. Sin sesión → login con `?volver=`; 403/404/500 → `views/includes/error.php`.
- **Todo lo que guarda va por fetch** a `public/api.php?accion=tema.metodo`, que solo acepta las acciones de su lista fija (método HTTP, Controller y método). Respuesta siempre JSON: `{ok, datos, mensaje?, redireccion?}` o `{ok: false, mensaje}`; si faltan requisitos salteables, `{ok: false, confirmar: true, avisos}` (409) y el JS repite con `confirmarIgual=1`.
- Códigos (`Respuesta::desdeExcepcion`): sesión 401, `AccesoDenegado` 403, CSRF 403, pedido más grande que `post_max_size` 413 (lo detecta `Api` antes del CSRF), `NoEncontrado` 404, `RequisitosPendientes` 409, `ReglaNegocio` 422, cualquier otra 500 con mensaje genérico y el detalle a `error_log`. (No usar códigos raros como 419: Apache los cambia por 500.)
- Un Controller recibe arrays (`$get`, `$post`, `$files`) y devuelve una `Respuesta` (página, JSON, redirect o archivo): no lee superglobales, no manda headers, no aplica reglas de negocio. Lee los datos con `texto()`, `entero()`, `booleano()` de `Controlador`. Se prueba por CLI.
- Mensajes después de un redirect: `Educarama\Helper\Flash` (`guardar` / `tomar`).
- Redirecciones con datos del navegador (`volver`): solo rutas `views/pages/*.php` (`Controlador::rutaInterna`).

### Formularios POST

- **Todo formulario POST usa `Educarama\Helper\Csrf`**: `<?= Csrf::campo() ?>` en la vista (el fetch manda el FormData con ese campo; un botón suelto usa el token de la `<meta name="csrf-token">` de la cabecera); `Api` lo valida antes de llamar al Controller.
- Los mensajes de error al usuario nunca muestran rutas del disco ni detalles internos: solo los de `ServicioException`.

### Sesión

- La sesión se arranca siempre con `Educarama\Helper\Sesion::iniciar()` (cookie httponly, SameSite=Lax, `use_strict_mode`), nunca con `session_start()` suelto.
- **`UsuarioService` es el único Service que toca `$_SESSION`** (login, logout, usuario actual). Guarda solo el id del usuario; los datos se releen de la base. Los demás Services reciben `$idUsuario` como parámetro: el Controller lo obtiene con `$usuarioService->exigirSesion()->id`.
- Al loguearse se renuevan el id de sesión y el token CSRF.

## Convenciones de código

- Carpetas en inglés, clases en castellano (`ProtagonistaRepository`).
- Cada archivo empieza con `declare(strict_types=1);` y su `namespace`. Namespace = carpeta (`Educarama\Model` → `src/Model/`, `Educarama\Config` → `config/`). Nombre de clase = nombre de archivo, mayúsculas incluidas.
- Autoload propio con `spl_autoload_register` en `config/bootstrap.php`. Cada página y cada punto de entrada de `public/` incluye solo `src/main.php` (que incluye `bootstrap.php` y devuelve el contenedor); nunca `require_once` de clases.
- **Inyección de dependencias (v2.1):** cada clase recibe sus dependencias por constructor, obligatorias (`private readonly`); nunca `new` de un colaborador ni `?? new`. Repositories reciben `PDO`; Services, `PDO` + Repositories + Services; Controllers, `UsuarioService` + Services. Toda clase nueva se registra en `src/main.php`. El contenedor (`$app->obtener()`) solo se usa en las puertas de entrada (páginas, `api.php`, `descargar.php`), nunca dentro de una clase.
- Links siempre con `url('views/pages/x.php')`; nunca `/Educarama/` escrito a mano.
- Conexión: `Educarama\Config\Database::getConexion()` (PDO singleton, excepciones, FETCH_ASSOC, sin emulación de prepares). Solo la llama `src/main.php`.
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
- Pantallas de la solicitud de alta: `solicitud_alta.php` (cualquier usuario con sesión: el formulario con secciones como filas nombre + rama, y mis solicitudes con el motivo si se rechazaron; con una pendiente, sin formulario) y `solicitudes.php` (solo admin, 403 al resto: las pendientes, de la más vieja a la más nueva, con quién las envió; aprobar o rechazar con motivo).

### Accesos (gestión de roles)

- Se suma a alguien buscando su cuenta **ya registrada** por email; si no hay, se le pide que se registre (sin invitaciones por ahora).
- Jefe de grupo: agrega, cambia y quita accesos del grupo y de cualquier sección de su grupo, con cualquier rol.
- Jefe de sección: solo cambia el rol de los ya asignados a su sección, **hasta Responsable**. No agrega, no quita y no toca a otro Jefe (ni a sí mismo). Nombrar Jefes de sección es del Jefe de grupo.
- El **último Jefe de un grupo** no se puede quitar ni bajar de rol, ni siquiera él mismo.
- Ver la lista (nombre, email, rol): la del grupo, el Jefe de grupo; la de una sección, su Jefe y el Jefe de grupo. Sin ningún acceso al grupo o a la sección, 404; con acceso pero sin ser Jefe, 403.
- Pantalla: `accesos.php?id_grupo=` / `?id_seccion=`. Cada fila trae los roles que se le pueden asignar y si se puede quitar (`AccesoEnPantalla`): al único Jefe del grupo no se le ofrece nada, y al Jefe de sección solo cambiar el rol hasta Responsable de quien no sea Jefe. Si el Jefe de grupo se baja el rol o se quita a sí mismo, vuelve a la pantalla del grupo o al inicio (ya no ve la lista).

### Progresión

- **Etapa actual** = la última lograda (`etapa_lograda` es la etapa **completada**). **Etapa en curso** = la siguiente a la actual, o la primera de la rama si no logró ninguna. Al elegir una competencia (pasarla a En proceso), su `id_etapa` toma la etapa en curso.
- **Cupo:** se cuentan las competencias con `id_etapa` igual a la etapa y se comparan con su `competencias_etapa`. Dos avisos, los dos se pueden saltear:
  1. Al elegir: si en proceso + desarrolladas de la etapa en curso ya llegan al cupo, se avisa que está eligiendo de más; si el educador confirma, se registra igual.
  2. Al completar: cuando las desarrolladas de la etapa en curso llegan al cupo, se sugiere confirmar la etapa.
- **Competencia desarrollada:** LyL requiere todas sus acciones requeridas (`es_opcional = 0`); Scouts y Caminantes, todas sus acciones; Rovers, nada. Si falta algo, se avisa y se puede saltear. Al cumplir la última acción, se sugiere "¿dar la competencia por desarrollada?".
- **Confirmar una etapa:** requiere el cupo de competencias desarrolladas y todas sus tareas (`tarea_etapa`); las dos cosas se pueden saltear mostrando lo adeudado. Lo que **bloquea** (no se saltea) es que la etapa anterior no esté lograda. En Caminantes el elemento es **obligatorio**; en las demás ramas el Service lo rechaza.
- **Quién deshace:** destildar acciones y volver atrás el estado de una competencia, Responsable en adelante. Quitar una etapa lograda o una insignia otorgada, solo Jefe.
- **Acceso:** leer, Ayudante; sin ningún acceso a la sección del protagonista, **404** (no se confirma que existe); con acceso pero sin el rol, 403.
- **Fechas:** confirmar una etapa y otorgar una insignia aceptan una fecha (la de la ceremonia o la entrega física): opcional, por defecto hoy, **nunca futura**. Las demás operaciones usan siempre la fecha de hoy.
- **Casos borde:** si ya logró todas las etapas, una competencia nueva se asigna a la última etapa de la rama, con aviso de cupo excedido. Solo se puede quitar la **última** etapa lograda. Retroceder una competencia a Pendiente conserva sus acciones cumplidas. No se pueden tildar acciones de una competencia Pendiente: primero hay que elegirla.
- **Insignias: registro aparte y manual.** No están atadas a confirmar una etapa. Se registran a mano en un submenú propio del protagonista, con su propia fecha (la de la entrega física), en `insignia_otorgada`. La insignia tiene que ser de la rama de la sección del protagonista (las de `id_rama` NULL valen para cualquiera). Según el programa, la insignia de una etapa se entrega cuando la etapa empieza: apenas una etapa pasa a estar en curso, si el protagonista no tiene su insignia se avisa "tiene la etapa X en curso y todavía no recibió su insignia". Nada automático. `etapa_lograda` no tiene relación funcional con la insignia.

### Protagonistas y patrullas

- Obligatorios: DNI (entero positivo), nombre y apellido. Fecha de nacimiento opcional. Año de ingreso opcional y no futuro.
- El DNI es único en `persona`: si ya existe, el alta se rechaza, aunque sea de un familiar.
- Pase de sección: método aparte, solo dentro del mismo grupo, con Responsable en las dos secciones. Queda sin patrulla; la progresión de la rama anterior queda como historial.
- Eliminar un protagonista (Jefe) borra en cascada su progresión, documentos y vínculos; su `persona` queda.
- Pase de sección: el perfil ofrece solo las otras secciones del grupo donde el usuario es Responsable (`ProtagonistaService::seccionesDestino`).
- Patrullas: el nombre no se repite dentro de la sección. Crear, renombrar y asignar integrantes, Responsable; eliminar, Jefe (los integrantes quedan sin patrulla). Se asignan desde el formulario del protagonista o desde el perfil de la patrulla; "quitar" desde la patrulla solo vale si es integrante de ESA patrulla. Solo hay patrullas en las ramas con `pequenio_equipo` en `nomenclatura` (Rovers no).
- Sin ningún acceso a la sección: **404** (no se confirma que existe); con acceso pero sin el rol, 403 (`PermisoService::exigirVisibleEnSeccion`). Vale también para familiares.

### Secciones

- `SeccionService`: `encabezado()` (cualquier acceso a la sección; sin acceso, 404) devuelve `SeccionEnPantalla` (sección, rama, grupo, términos, rol del usuario y `puedeVerDetalle` / `puedeEditar` / `puedeEliminar`). `listarPorGrupo()`: con cualquier rol de grupo, todas las secciones; sin rol de grupo, solo aquellas a las que tiene acceso; sin nada, 404.
- `grupo()` (pantalla del grupo: el grupo, el rol de grupo y las secciones que ve), `misGrupos()` (para el inicio: los grupos donde tiene rol de grupo o alguna sección) y `conteos()` (cantidad de protagonistas y por patrulla; sin datos personales, así que desde Supervisor).
- Las secciones no se crean, renombran ni borran desde la aplicación: hoy solo nacen al aprobar una solicitud de alta.
- Páginas: `perfil_seccion.php?id=` (encabezado, números, próximos eventos), `grupo.php?id=` (secciones y próximos eventos) e `inicio.php` (mis grupos con sus próximos eventos; sin accesos, el estado de mi solicitud de alta).

### Familiares

- Se gestionan siempre desde un protagonista: permiso en su sección y el familiar tiene que estar vinculado a él. Leer, Ayudante; alta, edición y desvincular, Responsable.
- Obligatorios: DNI, nombre y apellido. Teléfono (texto libre) y email (validado) opcionales. Contactos de emergencia libres (varios o ninguno); los listados los muestran primero.
- Alta con un DNI ya cargado: aviso con solo el nombre (nunca a quién más está vinculado) y, si se confirma, solo se crea el vínculo; no se pisan datos guardados. Si la persona existe pero no es familiar (ej. un hermano Rover), se crea su ficha de familiar sobre esa persona.
- Editar un familiar compartido cambia sus datos para todos sus protagonistas; el vínculo (`es_familia`) es de cada protagonista. El formulario avisa si está vinculado a otros (`FamiliarService::vinculadoAOtros`: solo sí o no, nunca cuántos ni cuáles).
- Desvincular: si queda sin protagonistas, aviso con su nombre y, solo si se confirma, se borra su ficha de `familiar` (la `persona` queda).

### Documentos

- Ver y descargar, Ayudante; subir y editar tipo/descripción, Responsable; eliminar, Jefe.
- PDF, JPG y PNG hasta 5 MB (`ALMACEN_*`). `tipo_documento` texto libre opcional. En disco: `documentos/protagonista/{id}/`.
- Pertenencia: documento → protagonista → sección → acceso. Sin acceso a la sección, **404** (no se confirma que existe); con acceso pero sin el rol, 403.
- Subir: primero el archivo, después la fila (si la fila falla, se borra el archivo). Eliminar: primero la fila, después el archivo. Eliminar un protagonista borra también su carpeta.
- PDF siempre como adjunto; imágenes pueden mostrarse en línea.

### Eventos

- De todo el grupo (sin filas en `evento_seccion`) o de algunas secciones del mismo grupo. Al crear o editar se pasa la lista completa de secciones (vacía = todo el grupo) y se reemplaza en una transacción.
- Crear, editar, desactivar y reactivar: evento de todo el grupo, Responsable de grupo (o Jefe de grupo); de algunas secciones, Responsable en **cada una**. Eliminar: lo mismo, con Jefe. Editar pide el permiso sobre el evento como está y como queda.
- Ver: los de todo el grupo, cualquiera con acceso al grupo o a alguna de sus secciones; los de algunas secciones, quien tiene acceso a una de ellas, más los roles de grupo. El Supervisor ve solo los `es_publico`. Si no lo puede ver, 404.
- Pantallas: `eventos.php?id_grupo=` pide un rol de grupo (sin ningún acceso al grupo, 404; solo con secciones, 403) y `eventos.php?id_seccion=` pide acceso a la sección (sin acceso, 404). Cada evento trae si el usuario puede editarlo y eliminarlo.
- Formulario: ofrece solo las secciones donde el usuario es Responsable y "todo el grupo" solo si es Responsable de grupo (`EventoService::opcionesFormulario`). El alcance se elige explícito (`grupo` o `secciones` con al menos una).
- `proximosPorGrupo()` a quien entra solo por secciones le da los de esas secciones (para el inicio y la pantalla del grupo).
- Desactivar (`activo`) los oculta: los listados traen solo los activos y el Responsable puede pedir también los inactivos.
- Título (hasta 200) y descripción obligatorios. No se crea un evento que empieza antes de hoy; al editar, el inicio puede quedar en el pasado solo si no cambia. `fecha_fin` opcional, no anterior al inicio.
- Próximos eventos (home y perfil de sección): activos que no terminaron (`fecha_fin`, o `fecha_inicio` si no tiene fin, de hoy en adelante). "Hoy" usa `ZONA_HORARIA`. `id_tipo_evento` queda siempre en NULL.

### Pantalla de competencias del protagonista

- Una pestaña por área, con todas las competencias de la rama aunque no tengan fila (sin fila = pendiente).
- Cada competencia tiene un botón que **solo avanza**: Pendiente → En proceso (la elige para la etapa en curso; aviso de cupo) → Desarrollada (aviso de acciones).
- **Volver atrás:** botón ⋮ arriba de las pestañas, al lado del filtro, con la opción "Corregir". Activa un modo que permite retroceder (Desarrollada → En proceso → Pendiente, que borra la fila). Solo Responsable en adelante.
- **Acciones:** casillas sí/no dentro de cada competencia, en una sección desplegable. Rovers no las muestra.
- **Contadores:** "Etapa en curso: X/cupo" y "Total: X/total de la rama" arriba; en cada pestaña, las desarrolladas de esa área.
- **Filtro** arriba de las pestañas: Todas / Pendientes / En proceso / Desarrolladas.
- **Guardado inmediato:** cada clic se guarda en el momento por fetch, con operaciones de a una (no hay guardado por lote). Si hay un aviso (cupo excedido, acciones sin cumplir), primero se muestra la confirmación y después se guarda.
- `ProgresionService::resumen()` (Ayudante en adelante) devuelve una clase vista con: etapa actual, etapa en curso, contadores (etapa, total y por área), lo adeudado para confirmar la etapa en curso y el aviso de insignia pendiente.
- `ProgresionService::pantallaCompetencias()` (Ayudante en adelante) devuelve `PantallaCompetencias`: el resumen, cada área con todas sus competencias (estado, `id_etapa` y acciones con cumplida sí/no), los términos de `nomenclatura` y `puedeEditar` (Responsable) / `puedeQuitar` (Jefe).
- Cada clic (`progresion.*` en `api.php`) responde `{sugerencias, resumen}`: el resumen actualizado repinta contadores, adeudado e insignia pendiente sin recargar (`public/js/competencias.js`).
- **Sugerencias con botón:** "¿Dar la competencia por desarrollada?" la avanza ahí mismo si se acepta; "¿Confirmar la etapa?" lleva a Etapas e insignias. Si no se acepta, no pasa nada.
- Cada competencia elegida muestra en qué etapa se eligió (`PantallaCompetencias::nombresEtapas`).

## Views (paso 5)

- **Estilos:** los del legacy (`public/css/styles.css`) corregidos y alineados con el manual de marca de Scouts de Argentina (plan en `docs/prompt-imagen-de-marca.md`). No es un rediseño.
  - **Color:** toda la UI sale de variables de `:root` y de las clases de rama; **ningún hex suelto** fuera de ese bloque, ni en vistas ni en JS. Violeta de Educarama `#4b1278` (el oficial oscurecido dos pasos); Orquídea, Púrpura, Lila, Celeste y Amarillo con su valor oficial. Clases `rama-lobatos`, `rama-scouts`, `rama-caminantes`, `rama-rovers` (los tres tonos de cada bandera): `--rama-primario`, `--rama-secundario`, `--rama-oscuro`, `--rama-contraste` (el único legible como texto sobre blanco) y el par del banner `--banner-*`. `Vista::claseRama($idRama)` las elige **por id de rama**, nunca por el nombre de la sección; `Vista::colorRama()` da `--rama-contraste` para usar fuera de la clase.
  - **Botón primario** (`.boton-primario`, variables `--boton-*`): Celeste con texto oscuro en páginas sin rama; violeta en páginas con rama (la clase va en el `<body>`). No usar `btn-primary` (el azul de Bootstrap no es de la paleta).
  - **Tipografía:** `--fuente` (Ubuntu, Arial de respaldo) para todo; `--fuente-condensada` (Ubuntu Condensed, solo peso 400, con `font-synthesis: none`) solo para etiquetas, badges y celdas y encabezados de las nóminas. Nunca condensada para texto corrido, el `h1` ni por debajo de 12 px.
  - **Contraste:** texto 4,5:1 y foco 3:1 (`--foco` violeta; sobre el header violeta, amarillo).
- **Marcas oficiales = dato, no marca de la app.** Sí: insignias, logos de rama como identidad de sección, pictogramas oficiales, atribución en el pie. No: favicon, header, login ni pantallas de carga (ahí va solo `LogoBaseV4.png` de Educarama). Nunca teñidas, en grises ni con filtros, y siempre sobre fondo claro: un estado (pendiente, inactivo) se dice con un badge, no alterando la imagen. El pie aclara que es un proyecto académico sin vinculación oficial.
- **Tres formas de mostrar una rama:** `Vista::logoRama()` (logo circular oficial, en las tarjetas de sección), `Vista::pictogramaRama()` (huella, rosa de los vientos, sol, cayado: en tablas y líneas de texto) y `Vista::iconoRama()` (Bootstrap, último respaldo). Las áreas usan `Vista::iconoAreaOficial()` y los conceptos sueltos `Vista::iconoOficial($slug)` (clase `.icono-oficial`, `alt=""` si el texto de al lado ya lo dice). Todo método que devuelve null cae en el ícono de Bootstrap. El menú lateral sigue con íconos de Bootstrap.
- **Cada página** hace: `$app = require_once` de `src/main.php`, `$datos = Pagina::cargar(fn () => $app->obtener(XController::class)->paginaY($_GET));`, define `$tituloPagina` (y opcionalmente `$claseRama`, `$sinMenu`, `$contenedorAngosto`, `$scriptsPagina`) y envuelve su contenido con `views/includes/cabecera.php` y `pie.php`. La cabecera pide el menú (`MenuController`) antes de imprimir nada y muestra los mensajes Flash. Login, registro y la página de error van con `$sinMenu = true`.
- **Todo dato impreso con `e()`** (escapa HTML; vive en `config/bootstrap.php`). Links con `url()`. Fechas con `Vista::fecha()` / `Vista::periodo()`. Fechas lejanas (nacimiento) se cargan con `Vista::campoFecha()`: tres listas día/mes/año, sin el calendario del navegador (su botón "Hoy" se confundía con enviar).
- **Menú lateral = drawer** (un offcanvas de Bootstrap). Desde 992 px está a la vista, empuja el contenido y se oculta con el botón de la barra superior (se recuerda en `localStorage`); por debajo es un cajón que se abre encima del contenido, con fondo oscuro. Es **contextual**: por cada grupo del usuario, sus links y sus secciones con los links que su rol permite (un link que no puede usar no se muestra); al final Mi cuenta, Solicitar alta, Solicitudes (admin) y Cerrar sesión.
- **JavaScript:** `public/js/educarama.js` hace los pedidos a `api.php` (token CSRF, flujo del 409 con `confirmarIgual`, redirecciones, error genérico) y conecta formularios y botones declarados con `data-accion`, `data-datos`, `data-confirmar`, `data-texto`, `data-boton`, `data-peligro`, `data-reiniciar`, `data-recargar`: la mayoría de las páginas no necesita JS propio. Las pantallas complejas tienen su archivo en `public/js/` (se declara con `$scriptsPagina`). Sin SweetAlert2 (sin internet) usa los cuadros nativos.
- **Cosas del legacy ya corregidas:** texto amarillo sobre blanco en el menú de Manada; el banner violeta con texto oscuro (ahora `--banner-*`, con texto blanco o oscuro según la rama, y el banner de Rovers termina en rojo oscuro); links `/Educarama/...` a mano; Ubuntu cargada dos veces; `<h1>` duplicado (la barra superior no lo es); menú móvil de íconos de 68 px (ahora es el drawer); `.sidebar` con `!important` en un `<style>` del include.
- **Perfil del protagonista** (`perfil_protagonista.php`): dos pestañas. **Perfil** (datos, resumen de progresión con los links a Competencias y a Etapas e insignias, familiares) y **Documentos** (ver, descargar, subir en un modal, editar tipo y descripción, eliminar). El ⋮ del encabezado: Editar datos, Cambiar de sección, Eliminar, según el rol. `#documentos` en la URL abre esa pestaña (`educarama.js` lo hace con cualquier página de pestañas); subir y eliminar un documento vuelven ahí.
- **Etapas e insignias** (`progresion.php?id=`, el submenú propio del protagonista): las etapas de la rama (logradas con fecha y elemento; la última se quita, solo Jefe; la en curso con sus tareas tildables, lo que le falta y "Confirmar etapa" con fecha y, en Caminantes, elemento), las insignias de la rama y generales (registrar la entrega con fecha; quitar, solo Jefe) y las especialidades (LyL y Scouts). Lo arma `ProgresionService::pantallaEtapas()`. Cada acción recarga la página.
- **Insignias con imagen:** `insignia.imagen` guarda solo el nombre del archivo (la carpeta la pone `Vista::imagenInsignia()`, en `Insignias/Progresion/`). Lobatos, Scouts y Rovers la traen en la fila. Caminantes (etapas 9 a 12) queda en NULL: la imagen sale del elemento elegido en `etapa_lograda` (`Vista::imagenInsigniaCaminantes()`), así que una etapa en curso muestra el ícono genérico aunque la insignia ya esté entregada (aceptado). Ciudadanía no tiene imagen. Con imagen se muestra a 88 px, sin marco (la forma cambia por rama); sin imagen, el círculo con el ícono. La Promesa es un dato del protagonista, no una imagen.
- **Pantallas hechas:** login, registro, inicio, mi cuenta, solicitud de alta, error, `perfil_protagonista`, `gestionar_competencias`, `progresion`, `protagonista_form`, `familiar_form`, `protagonistas`, `patrullas`, `perfil_patrulla`, `perfil_seccion`, `grupo`, `eventos`, `evento_form`, `accesos`, `solicitudes` y `secciones`.

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

Baja lógica con respaldo de 1 mes · integración con Cruz del Sur (`ValidadorCruzDelSur`, resultado al revisar solicitudes, `email_validado`) · envío de emails (aviso de solicitud rechazada: `SolicitudAltaService::notificarRechazo`) · recuperación de contraseña (`token_recuperacion` reservado; necesita envío de emails) · bloqueo por intentos de login fallidos · invitar por email a quien no tiene cuenta (al dar un acceso) · activar la versión A de contraseña · plantillas de evento (`id_tipo_evento` reservado) · acciones personalizadas de protagonistas · momentos de la progresión · auditoría de confirmaciones · habilitaciones que bloqueen · generador del catálogo desde CSV (reservado fuera del repo) · íconos oficiales en el menú lateral (hoy `$enlaceMenu` en `menu.php` arma un `<i class="bi …">`: habría que cambiar su firma para aceptar una imagen).

### Nóminas: columnas personalizables y filtros encadenados

Hoy las dos tablas de `protagonistas.php` (nómina general `#tablaNomina` y de sección `#tablaNominaSeccion`) tienen columnas fijas, se desplazan de costado en pantallas chicas (poco claro) y se ordenan solo por nombre, edad y competencias. Los filtros (grupo, sección, rama; patrulla en la de sección) son independientes entre sí.

1. **Columnas personalizables.** Un botón "Columnas" (dropdown de Bootstrap con casillas) arriba de la tabla elige qué columnas se ven. Cada `<th>` y `<td>` lleva `data-columna="clave"`; el JS oculta con una clase (`d-none`) las no elegidas. Nombre siempre visible. Columnas posibles, todas con datos que ya trae `ProtagonistaEnNomina` (sumar al Repository las que falten, sin datos sensibles para Supervisor): DNI, edad, fecha de nacimiento, sección, grupo, patrulla, etapa actual, etapa en curso, competencias (desarrolladas / total), promesa, año de ingreso. La elección se recuerda por usuario y por tabla en `localStorage` (con try/catch; sin almacenamiento, las columnas por defecto). En celular arranca con pocas columnas (nombre, sección o patrulla, etapa), así no hace falta el desplazamiento lateral.
2. **Ordenar por cualquier columna visible.** Todo `<th>` con `data-orden`, con la flecha que indica el sentido (`aria-sort` para lectores de pantalla). El valor de orden va en `data-valor` de cada celda (número, fecha ISO o texto normalizado sin acentos), no en el texto visible.
3. **Filtrar por valor.** Además de la búsqueda por nombre, filtros por los valores de las columnas: listas para las categóricas (patrulla, etapa, promesa sí/no) y rango para las numéricas (edad desde/hasta, competencias mínimas). Un botón "Limpiar filtros" y el contador "Mostrando X de Y".
4. **Filtros encadenados.** Elegir un grupo deja en el filtro de sección solo las secciones de ese grupo (las otras `disabled`, no borradas, para no perder el orden) y resetea la sección si ya no corresponde; elegir una rama limita las secciones a esa rama; elegir una sección fija grupo y rama. En la nómina de sección, la patrulla ya es de esa sección. Cada `<option>` de sección lleva `data-grupo` y `data-rama` para resolverlo en el navegador, sin pedidos al servidor.
5. Todo en un `public/js/nomina.js` (sacar el JS en línea de `protagonistas.php`), sin librerías nuevas, compartido por las dos tablas.

## Orden de trabajo

1. ~~Diagrama ER, `educarama.sql`, config, autoload y Models~~ ✅
2. ~~Repositories~~ ✅
3. ~~Services~~ ✅
4. ~~Controllers~~ ✅
5. ~~Views~~ ✅ (todas las pantallas), con la alineación con la imagen de marca (T1–T7 de `docs/prompt-imagen-de-marca.md`) ✅
6. Pruebas en el navegador y correcciones (en curso).
7. ~~v2.1: inyección de dependencias con Composition Root (`src/main.php`)~~ ✅. Quedan los errores de la revisión del 15/11/2026: lista en `docs/explicacion-de-cambios.md` (sección "Revisión de la v2.1").
