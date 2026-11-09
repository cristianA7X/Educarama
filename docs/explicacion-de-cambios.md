# Explicación de cambios — Educarama v2

Iniciado el 01/11/2026.

Registro de los cambios del rediseño (rama `refactor/v2`): por qué se hizo cada uno y cómo funciona el código nuevo. Se suma una sección por cambio importante.

## Estructura de carpetas

Cada carpeta tiene un solo tipo de archivo. En el legacy, `src/Datos/` mezclaba repositorios, la conexión y los `.sql`, y no quedaba claro qué era código de la aplicación y qué no.

| Carpeta | Qué contiene | Namespace |
| --- | --- | --- |
| `config/` | Configuración, conexión a la base y arranque (`bootstrap.php`) | `Educarama\Config` |
| `database/` | `educarama.sql`. No es código de la aplicación | — |
| `src/Model/` | Una clase por tabla, con sus datos | `Educarama\Model` |
| `src/Model/Enum/` | Listas cerradas de valores (estados, prioridades, niveles de rol) | `Educarama\Model\Enum` |
| `src/Repository/` | Consultas SQL; devuelven Models | `Educarama\Repository` |
| `src/Service/` | Reglas de negocio (permisos, validaciones, avisos de progresión) | `Educarama\Service` |
| `src/Controller/` | Reciben lo que manda la vista y llaman al Service | `Educarama\Controller` |
| `src/Helper/` | Funciones de apoyo para las vistas | `Educarama\Helper` |
| `public/` | Punto de entrada (`index.php`), CSS y fuentes | — |
| `views/` | Páginas, includes e imágenes | — |
| `docs/` | Este archivo | — |

Las carpetas van en inglés y las clases en castellano (`ProtagonistaRepository`). Lo importante es que el nombre de la carpeta coincida con el namespace: de eso depende que el autoload encuentre las clases.

## Autoload

Las clases se cargan solas: cada página incluye un único archivo, `config/bootstrap.php`, y no hay más `require_once`. En el legacy, cada archivo traía sus propios `require_once` con rutas escritas a mano, y varias estaban rotas (apuntaban a carpetas que no existían, como `Dominio/` o `Repositorio/`).

**No hace falta instalar nada.** El autoload usa `spl_autoload_register()`, una función que trae PHP. Se descartó Composer: en este proyecto solo habría servido para generar este mismo autoload, y cada integrante del equipo habría tenido que instalarlo. Si algún día hace falta una librería externa, se puede sumar sin reescribir nada, porque la regla para encontrar los archivos es la misma.

**Cómo funciona.** Cuando el código usa una clase que todavía no se cargó, PHP llama a la función registrada en `bootstrap.php` con el nombre completo de la clase. La función traduce ese nombre a una ruta y hace el `require`. La regla (llamada PSR-4) es que el namespace indica la carpeta y el nombre de la clase, el archivo:

```
Educarama\Config\Database      →  config/Database.php
Educarama\Model\Protagonista   →  src/Model/Protagonista.php
Educarama\Model\Enum\NivelRol  →  src/Model/Enum/NivelRol.php
```

Por eso cada archivo empieza con su `namespace`, y el nombre de la clase tiene que coincidir con el del archivo, mayúsculas incluidas. En Windows da igual, pero en un hosting Linux no. Si una página muestra "Class ... not found", el error está en uno de esos tres datos.

**Cómo se usa en una página:**

```php
require_once __DIR__ . '/../../config/bootstrap.php';

use Educarama\Model\Protagonista;

$protagonista = Protagonista::desdeFila($fila);
```

Agregar una clase nueva no requiere ningún paso extra: alcanza con crear el archivo en la carpeta que corresponde a su namespace.

## Configuración y BASE_URL

La URL base y los datos de conexión están en un solo archivo, `config/config.php`. Antes, `/Educarama/` estaba escrito a mano en cada link, y renombrar la carpeta o subir el proyecto a un hosting obligaba a cambiar todos los archivos.

**La constante `BASE_URL`** vale `'/Educarama'`: es la parte de la dirección que va después de `http://localhost`. Los links se arman con la función `url()`, que está en `config/bootstrap.php`:

```php
<a href="<?= url('views/pages/protagonistas.php') ?>">Protagonistas</a>
<link rel="stylesheet" href="<?= url('public/css/styles.css') ?>">
```

Si la carpeta cambia de nombre, se cambia solo `BASE_URL`.

**Configuración de cada máquina.** Los valores por defecto son los de un XAMPP recién instalado: usuario `root`, sin contraseña, base `educarama_v2`. Si a alguien le cambia algo, no edita `config.php`: crea `config/config.local.php` y define ahí solo lo distinto.

```php
<?php
define('DB_PASS', 'mi_contraseña');
```

Ese archivo está en `.gitignore`, así que la contraseña de uno no le cambia la configuración a los demás.

**Conexión.** `Educarama\Config\Database::getConexion()` crea la conexión la primera vez que se pide y después devuelve siempre la misma. Está configurada para lanzar una excepción ante cualquier error de SQL y para que los números lleguen como `int` y no como texto.

## Models

Hay un Model por tabla (31 en total, en `src/Model/`) y los repositorios siempre devuelven Models, nunca arrays. En el legacy los Models existían pero casi no se usaban: los repositorios devolvían arrays, y un error de tipeo en una clave (`$p['nombr']`) no avisaba nada.

**Cómo es un Model.** Una clase con propiedades públicas, una por columna, declaradas en el constructor (sintaxis de PHP 8). Las columnas de la base van en `snake_case` y las propiedades en `camelCase`:

| Columna en la base | Propiedad en PHP |
| --- | --- |
| `id_seccion` | `$idSeccion` |
| `anio_ingreso` | `$anioIngreso` |
| `es_contacto_de_emergencia` | `$esContactoDeEmergencia` |

**`desdeFila()`.** Cada Model tiene este método estático, que recibe la fila tal como la devuelve PDO y arma el objeto. Es el único lugar donde se traducen los nombres de columna y se convierten los tipos: `1`/`0` pasa a `true`/`false`, el texto JSON pasa a array y el texto de un ENUM pasa a su enum de PHP.

```php
$fila = $stmt->fetch();                        // ['id' => 4, 'id_seccion' => 2, 'promesa' => 1, ...]
$protagonista = Protagonista::desdeFila($fila);

echo $protagonista->idSeccion;                 // 2
if ($protagonista->promesa) { ... }            // true
```

El editor autocompleta las propiedades y marca en rojo una que no existe.

**Enums** (`src/Model/Enum/`). Las columnas ENUM de la base tienen su enum de PHP: `EstadoProgresion`, `Elemento`, `EstadoSolicitud` y `Prioridad`. Además, `NivelRol` le pone nombre a los niveles de rol. Un enum no acepta valores fuera de la lista: una prioridad `'urgente'` tira error en vez de guardarse. Cada uno tiene `etiqueta()` para mostrarlo en pantalla:

```php
$competencia->estado === EstadoProgresion::Desarrollada;   // comparar
$competencia->estado->etiqueta();                          // 'Desarrollada'
$competencia->estado->value;                               // 'desarrollada' (lo que se guarda)
```

**Reglas que siguen todos:**

- `$id` es `null` en un objeto nuevo que todavía no se guardó. El repositorio lo completa al insertar.
- Las tablas sin `id` propio (las de acceso, las de vínculo y las de progresión) se identifican por sus dos claves, por ejemplo `idProtagonista` + `idCompetencia`.
- Las fechas son texto con formato `'AAAA-MM-DD'`, igual que en MySQL.
- `Usuario` tiene el hash de la contraseña: nunca se manda entero a una vista.
- Un Model no tiene SQL ni reglas de negocio: solo datos. Las consultas van en `Repository` y las reglas en `Service`.
- Cuando una pantalla necesita datos de varias tablas juntas (un protagonista con el nombre de su sección y de su rama), el repositorio devuelve una clase "vista" aparte, por ejemplo `PerfilProtagonista`, en lugar de agregarle campos a `Protagonista`.

Se verificó que los 31 Models coinciden columna por columna con `database/educarama.sql`.

## Repositories de tablas estáticas

Un repositorio es la clase que habla con la base para una tabla: tiene el SQL y devuelve Models. Las páginas y los Services nunca escriben SQL; le piden los datos al repositorio. Están en `src/Repository/`.

**Dos clases base.**

- `RepositorioBase`: la usan todos los repositorios. Tiene la conexión y dos ayudas, `listar()` y `buscarUno()`, que ejecutan una consulta preparada y convierten cada fila en un Model con su `desdeFila()`. El SQL lo escribe siempre cada repositorio, a la vista; la base no arma consultas.
- `RepositorioEstatico`: hereda de la anterior y la usan los repositorios de tablas estáticas. Son **solo lectura**: no tienen métodos para insertar, modificar ni borrar, porque esas tablas las cargan los devs desde `educarama.sql`. En el legacy, `CategoriaRamaController/Repository` permitía escribir una tabla estática.

```php
final class EtapaRepository extends RepositorioEstatico
{
    public function listarPorRama(int $idRama): array
    {
        return $this->listar('SELECT * FROM etapa WHERE id_rama = ? ORDER BY numero', [$idRama], Etapa::class);
    }
}
```

**Conexión.** El constructor acepta una conexión opcional. Normalmente se crea sin argumentos (`new RamaRepository()`) y usa `Database::getConexion()`. Pasarle una conexión propia sirve para pruebas o para que varios repositorios compartan una misma transacción.

**Caché.** Las tablas estáticas no cambian mientras se ejecuta una página, así que cada consulta con los mismos parámetros va a la base una sola vez por pedido. Si una página pide la nomenclatura 20 veces, hace 1 consulta. Detalles:

- La caché guarda las filas, no los objetos: cada llamada arma Models nuevos. Si alguien hace `$rama->titulo = 'x'`, no afecta a las lecturas siguientes.
- Es una propiedad `static`, así que la comparten todas las instancias. Dura lo que dura el pedido HTTP y no hay que invalidarla nunca.
- Los repositorios de tablas dinámicas (protagonista, progresión...) heredan de `RepositorioBase`, sin caché.

**Nombres de métodos.** `buscarPorId()` / `buscar...()` devuelven un Model o `null`; `listar...()` devuelven un array de Models (vacío si no hay nada). Todos los listados tienen `ORDER BY`.

| Repositorio | Métodos |
| --- | --- |
| `RamaRepository` | `listarTodas`, `buscarPorId` |
| `AreaRepository` | `listarTodas`, `buscarPorId` |
| `AreaRamaRepository` | `listarPorRama` (las áreas de una rama con el nombre que tienen en ella) |
| `NomenclaturaRepository` | `buscar(clave, idRama)`, `listarPorRama` (indexado por clave) |
| `CompetenciaRepository` | `buscarPorId`, `listarPorRama`, `listarPorRamaYArea` |
| `AccionRepository` | `buscarPorId`, `listarPorCompetencia`, `listarPorRama` |
| `EtapaRepository` | `buscarPorId`, `listarPorRama` |
| `TareaEtapaRepository` | `listarPorEtapa` |
| `InsigniaRepository` | `buscarPorId`, `listarPorRama`, `buscarPorEtapa` |
| `EspecialidadRepository` | `buscarPorId`, `listarTodas`, `listarPorArea` |
| `RolRepository` | `listarTodos`, `buscarPorId`, `buscarPorNivel(NivelRol)` |

`AccionRepository::listarPorRama` trae todas las acciones de una rama en una sola consulta, para pantallas como `gestionar_competencias.php`. Sin eso, haría falta una consulta por cada competencia. Las acciones se agrupan en PHP por `$accion->idCompetencia`.

```php
$nomenclatura = (new NomenclaturaRepository())->listarPorRama($idRama);
echo $nomenclatura['areas']->texto;                       // 'Senderos' en LyL

$jefe = (new RolRepository())->buscarPorNivel(NivelRol::Jefe);
```

Se probaron todos los métodos contra `educarama_v2`: 186 competencias, 848 acciones, Rovers sin acciones y con las áreas 5–10, y la caché sin consultas repetidas.

## Repositories de grupo, sección, patrulla, persona y protagonista

Estos heredan de `RepositorioBase` (sin caché, porque los datos cambian) y además de leer también escriben.

**Nuevas ayudas en `RepositorioBase`:**

- `ejecutar($sql, $params)`: corre un INSERT/UPDATE/DELETE y devuelve cuántas filas afectó.
- `insertarFila($sql, $params)`: corre un INSERT y devuelve el id que asignó la base.
- `exigirId($id)`: lanza una excepción si se intenta actualizar un objeto que todavía no se insertó (id `null`).

**Escritura.** Cada repositorio tiene `insertar(Model): int` y `actualizar(Model): void`. `insertar` le completa el id al objeto, así se puede seguir usando:

```php
$persona = new Persona(null, 45123456, 'Ana', 'Pérez', '2015-03-04');
$personaRepo->insertar($persona);                 // ahora $persona->id tiene valor

$protagonista = new Protagonista(null, $persona->id, $idSeccion, null, false, 2025);
$protagonistaRepo->insertar($protagonista);
```

Si hacen falta varias escrituras que van juntas (persona + protagonista), el Service abre una transacción. Como la conexión es única, todos los repositorios participan de ella:

```php
$pdo = Database::getConexion();
$pdo->beginTransaction();
try { ...; $pdo->commit(); } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
```

**Borrado.** Por ahora solo hay `eliminar()` en dos repositorios, y es un borrado físico. La baja lógica queda para más adelante.

- `PatrullaRepository::eliminar`: los integrantes no se borran; quedan sin patrulla (la base pone `id_patrulla` en NULL).
- `ProtagonistaRepository::eliminar`: la base borra en cascada toda su progresión, sus documentos y sus vínculos con familiares. Su `persona` no se borra, porque puede ser además familiar de otro protagonista.

`Grupo`, `Seccion` y `Persona` no tienen `eliminar`.

**Sin "listar todo".** En el legacy, el listado de protagonistas mostraba todos los de la base, de cualquier grupo. Ahora solo se lista por sección o por patrulla. Qué secciones puede ver cada usuario lo decide el Service según sus accesos.

**Sin clases vista por ahora.** Cada repositorio devuelve solo su Model. Para mostrar un protagonista con su nombre, el Service pide el `Protagonista` y después la `Persona` con `$protagonista->idPersona`. Lo mismo con la sección y la patrulla. La rama sale de la sección (`$seccion->idRama`) y de `RamaRepository`, que tiene caché. Nunca se deduce del nombre de la sección, como hacía el legacy con `strpos`.

| Repositorio | Lectura | Escritura |
| --- | --- | --- |
| `GrupoRepository` | `buscarPorId`, `buscarPorCodigo` | `insertar`, `actualizar` |
| `SeccionRepository` | `buscarPorId`, `listarPorGrupo` | `insertar`, `actualizar` |
| `PatrullaRepository` | `buscarPorId`, `listarPorSeccion` | `insertar`, `actualizar`, `eliminar` |
| `PersonaRepository` | `buscarPorId`, `buscarPorDni` | `insertar`, `actualizar` |
| `ProtagonistaRepository` | `buscarPorId`, `listarPorSeccion`, `listarPorPatrulla` | `insertar`, `actualizar`, `eliminar` |

Los listados de protagonistas salen ordenados por apellido y nombre: el JOIN con `persona` se usa solo para ordenar y devuelve únicamente las columnas de `protagonista`.

Se probó todo contra `educarama_v2` dentro de una transacción que después se deshizo: insertar, actualizar, borrar, la cascada, el SET NULL de la patrulla y el booleano `promesa` ida y vuelta.

## Repositories de familiares, documentos, usuarios, accesos y solicitudes

Siguen las mismas reglas que los anteriores: heredan de `RepositorioBase`, tienen `insertar` y `actualizar` por separado, y devuelven solo su Model (sin clases vista).

**Tablas sin id propio** (`es_familia`, `acceso_grupo`, `acceso_seccion`). Se buscan y se borran por sus dos claves: `buscar($idUsuario, $idSeccion)`, `eliminar($idUsuario, $idSeccion)`. `insertar` no devuelve nada, porque no hay id que completar. Si la fila ya existe, la base lanza una excepción. `actualizar` cambia los demás campos; las dos claves identifican la fila y no se modifican. Esto encaja con la regla de roles: el Jefe de sección solo puede cambiar el rol de usuarios ya asignados, o sea, solo `actualizar`.

**Usuario y la contraseña.** El `Usuario` que devuelve el repositorio tiene el hash, porque el Service lo necesita para `password_verify()`. Por eso nunca se manda entero a una vista. Para que no se pise el hash por error:

- `actualizar(Usuario)` escribe nombre, email, `es_admin` y `email_validado`. **No toca la contraseña ni el token.**
- `cambiarContrasenia($idUsuario, $hash)` guarda la nueva contraseña (ya hasheada) y borra el token de recuperación, para que no se pueda reusar.
- `guardarToken($idUsuario, $token)` guarda el código de recuperación (o lo borra con `null`).

El legacy buscaba por email **y** token en el `WHERE`. Ahora el repositorio busca solo por email y el Service compara el token con `hash_equals()`, que tarda lo mismo acierte o no y no da pistas sobre el código.

**Accesos y el Jefe de grupo.** El Jefe de grupo no tiene filas en `acceso_seccion`: es Jefe de todas las secciones de su grupo por derivación. Por eso `AccesoSeccionRepository::listarPorSeccion()` no lo incluye; el Service lo suma mirando `acceso_grupo`.

**Solicitud de alta.** Una vez enviada no se edita, así que no hay `actualizar`. Solo se registra la revisión con `registrarRevision($id, EstadoSolicitud::Aprobada, $idAdmin)`. Los arrays `datosGrupo` y `datosSecciones` se guardan como JSON con `JSON_UNESCAPED_UNICODE`, así en la base se lee `"Sección"` y no `"Sección"`. Crear el grupo, las secciones y el acceso de Jefe al aprobar lo hace el Service, dentro de una transacción.

**Fechas que pone la base** (`documento.fecha_subida`, `solicitud_alta.fecha`). Después de `insertar`, el objeto tiene `id` pero la fecha sigue en `null`. Si hace falta, se vuelve a leer con `buscarPorId`.

**Borrado.**

- `FamiliarRepository::eliminar`: la base borra en cascada sus vínculos en `es_familia`. Su `persona` queda, porque puede ser además protagonista.
- `EsFamiliaRepository::eliminar`: desvincula; el protagonista y el familiar siguen existiendo.
- `DocumentoRepository::eliminar`: borra la fila. El archivo del disco lo borra el Service.
- `Acceso*Repository::eliminar`: quita el acceso.
- `Usuario` y `SolicitudAlta` no tienen `eliminar`.

| Repositorio | Lectura | Escritura |
| --- | --- | --- |
| `FamiliarRepository` | `buscarPorId`, `buscarPorPersona`, `listarPorProtagonista` | `insertar`, `actualizar`, `eliminar` |
| `EsFamiliaRepository` | `buscar`, `listarPorProtagonista`, `listarPorFamiliar` | `insertar`, `actualizar`, `eliminar` |
| `DocumentoRepository` | `buscarPorId`, `listarPorProtagonista` | `insertar`, `actualizar` (tipo y descripción), `eliminar` |
| `UsuarioRepository` | `buscarPorId`, `buscarPorEmail` | `insertar`, `actualizar`, `cambiarContrasenia`, `guardarToken` |
| `AccesoGrupoRepository` | `buscar`, `listarPorUsuario`, `listarPorGrupo` | `insertar`, `actualizar`, `eliminar` |
| `AccesoSeccionRepository` | `buscar`, `listarPorUsuario`, `listarPorSeccion` | `insertar`, `actualizar`, `eliminar` |
| `SolicitudAltaRepository` | `buscarPorId`, `listarPorEstado`, `listarPorUsuario` | `insertar`, `registrarRevision` |

Los familiares de un protagonista salen primero los contactos de emergencia, en el mismo orden en `FamiliarRepository` y en `EsFamiliaRepository`, así el Service puede recorrer las dos listas juntas.

Se probó todo contra `educarama_v2` dentro de una transacción que después se deshizo: cascadas, que `actualizar` no pise el hash ni la ruta del archivo, la PK duplicada y el JSON con acentos ida y vuelta.

## Repositories de progresión

Son los 6 repositorios de las tablas de progresión: `competencia_protagonista`, `accion_cumplida`, `tarea_etapa_cumplida`, `etapa_lograda`, `especialidad_protagonista` e `insignia_otorgada`.

**Cómo funcionan estas tablas.** Que no haya fila significa "pendiente". Cuando un educador confirma algo, se crea la fila con `insertar`. Avanzar de estado (en proceso → desarrollada) es un `actualizar`. Desmarcarlo (por ejemplo, destildar un checkbox) es `eliminar`: se borra la fila y vuelve a quedar pendiente. Ninguna tiene id propio; todas se identifican por el protagonista y el ítem: `buscar($idProtagonista, $idCompetencia)`.

**La fecha la pone el Service.** La columna `fecha` es obligatoria y viene en el Model; el repositorio guarda la que reciba. El Service pone la de hoy por defecto, pero el educador puede cargar otra (una etapa lograda en el campamento del mes pasado y registrada hoy).

```php
$repo->insertar(new AccionCumplida($idProtagonista, $idAccion, date('Y-m-d')));
```

**Etapa actual.** `EtapaLogradaRepository::buscarActual($idProtagonista, $idRama)` devuelve la etapa de mayor número lograda **dentro de la rama indicada** (la de la sección actual del protagonista):

- Se ordena por número de etapa y no por fecha, porque la fecha puede cargarse hacia atrás.
- Se filtra por rama porque, si un protagonista pasa de Scouts a Caminantes, sus etapas de Scouts siguen guardadas como historial (`listarPorProtagonista` las trae todas).

**Acciones de una competencia.** `AccionCumplidaRepository::listarPorProtagonistaYCompetencia()` trae solo las acciones cumplidas de esa competencia. Comparándolas con `AccionRepository::listarPorCompetencia()`, el Service sabe si las cumplió todas. El legacy (`verificarAccionesCompletas`) contaba las cumplidas de todas las competencias, y además usaba una tabla que no existía.

**Orden de los listados.** Salen en el orden del catálogo (área, competencia, número), el mismo de los repositorios estáticos, así la pantalla puede recorrer el catálogo y lo cumplido en paralelo. Las insignias otorgadas salen por fecha de entrega.

**Lo que NO hace el repositorio** (lo hace el Service):

- Contar cuántas competencias tiene desarrolladas y compararlo con los mínimos de la etapa. Lo adeudado se calcula en vivo y no se guarda.
- Validar que las especialidades sean de una rama con `usa_especialidades`, o las acciones de una con `usa_acciones`.
- Ofrecer la insignia al confirmar una etapa. Al borrar una etapa lograda, la insignia otorgada no se borra sola.
- Guardar varios checkboxes a la vez: el Service recorre la lista dentro de una transacción.

| Repositorio | Además de `buscar`, `listarPorProtagonista`, `insertar` y `eliminar` |
| --- | --- |
| `CompetenciaProtagonistaRepository` | `actualizar` (estado y fecha) |
| `AccionCumplidaRepository` | `listarPorProtagonistaYCompetencia` |
| `TareaEtapaCumplidaRepository` | `listarPorProtagonistaYEtapa` |
| `EtapaLogradaRepository` | `actualizar` (elemento y fecha), `buscarActual` |
| `EspecialidadProtagonistaRepository` | `actualizar` (estado y fecha) |
| `InsigniaOtorgadaRepository` | — |

Se probó todo contra `educarama_v2` dentro de una transacción que después se deshizo: etapa actual con fechas cargadas al revés y con cambio de rama, acciones de dos competencias sin mezclarse, el elemento de Caminantes ida y vuelta, y el borrado en cascada al eliminar el protagonista.

## Repositories de eventos

Son `EventoRepository` y `EventoSeccionRepository`. Con estos dos quedan hechos todos los repositorios.

**De quién es un evento.** Un evento siempre pertenece a un grupo (`evento.id_grupo`). Si no tiene filas en `evento_seccion`, es de **todo el grupo**. Si tiene filas, es solo de esas secciones.

**Eventos de una sección.** `EventoRepository::listarPorSeccion($idSeccion)` devuelve los dos casos juntos: los de todo el grupo y los asignados a esa sección. El grupo se saca de la propia sección con un JOIN, así no hace falta pasarlo y no aparecen eventos de otro grupo. `EventoSeccionRepository::listarPorSeccion()` hace otra cosa: devuelve solo las asignaciones explícitas, sin los eventos de todo el grupo.

En esa consulta el mismo id se pasa dos veces con nombres distintos (`:id_seccion` y `:id_seccion2`). Es necesario porque la conexión no emula los prepares, y en ese modo PDO no deja usar dos veces el mismo `:nombre`.

**Cambiar las secciones de un evento.** `eliminarPorEvento($idEvento)` borra todas sus filas, y el evento pasa a ser de todo el grupo. Para reemplazar la lista, el Service hace `eliminarPorEvento` y después un `insertar` por cada sección, todo dentro de una transacción. Ojo: si se quita la última sección con `eliminar`, el evento también pasa a ser de todo el grupo.

**Otros detalles.**

- `actualizar` no modifica `id_grupo`: un evento no se pasa a otro grupo.
- `eliminar` borra el evento, y la base borra en cascada sus filas de `evento_seccion`.
- Los listados traen eventos activos e inactivos, ordenados por `fecha_inicio`. Filtrar por `activo`, o por `es_publico` (lo que ve un Supervisor), lo hace el Service.
- Que las secciones sean del mismo grupo que el evento lo valida el Service.

| Repositorio | Lectura | Escritura |
| --- | --- | --- |
| `EventoRepository` | `buscarPorId`, `listarPorGrupo`, `listarPorSeccion` | `insertar`, `actualizar`, `eliminar` |
| `EventoSeccionRepository` | `listarPorEvento`, `listarPorSeccion` | `insertar`, `eliminar`, `eliminarPorEvento` |

Se probó todo contra `educarama_v2` dentro de una transacción que después se deshizo: un evento de todo el grupo y otro de una sola sección, vistos desde las dos secciones; la actualización con el enum, el bool y la fecha nula; la PK duplicada; el paso a todo el grupo al quitar las secciones; y el borrado en cascada.

## Base de los Services, excepciones y permisos

Es la base sobre la que se van a apoyar todos los Services: una clase común, las excepciones y el cálculo de permisos.

**Qué hace un Service.** Aplica las reglas de negocio y usa los repositorios para leer y guardar. No lee `$_SESSION` ni imprime HTML. Recibe el id del usuario actual como parámetro (lo saca de la sesión el Controller). Devuelve Models, o lanza una excepción si la operación no se puede hacer.

**`ServicioBase`.** Es la clase de la que heredan todos los Services.

- Cada Service recibe sus repositorios en el constructor como parámetros opcionales. Si no se pasan, crea los de siempre. Es el mismo criterio que `RepositorioBase` con el `PDO`, y permite probar un Service con repositorios falsos.
- `transaccion(callable)` ejecuta la operación y hace commit. Si algo lanza una excepción, hace rollback y la vuelve a lanzar. Funciona porque los Services y los repositorios comparten la misma conexión (`Database::getConexion()`).
- Si ya hay una transacción abierta (por ejemplo, un Service que llama a otro), `transaccion` no abre otra, porque MySQL no tiene transacciones anidadas. La operación queda dentro de la de afuera, y es la de afuera la que hace el commit o el rollback.

**Excepciones** (`src/Service/Excepcion/`). Todas heredan de `ServicioException`, y su mensaje se le puede mostrar al usuario tal cual. El Controller las atrapa juntas:

```php
try {
    $servicio->hacerAlgo($idUsuario, ...);
} catch (ServicioException $e) {
    $error = $e->getMessage();
}
```

| Excepción | Cuándo se lanza | Respuesta típica |
| --- | --- | --- |
| `ReglaNegocioException` | La operación viola una regla (patrulla de otra sección, acciones en Rovers) | Mensaje en el formulario |
| `AccesoDenegadoException` | El usuario no tiene el rol necesario | 403 |
| `NoEncontradoException` | Se pidió un id que no existe | 404 |

Una `PDOException` u otro error de PHP no es una `ServicioException`: es un error del sistema, y su mensaje no se le muestra al usuario. `RequisitosPendientesException` se agrega junto con `ProgresionService`.

**`PermisoService`.** Calcula qué rol tiene un usuario y corta la operación si no le alcanza. Sigue las reglas que se agregaron a CLAUDE.md:

- **En una sección:**
  1. Si es Jefe del grupo de la sección, es Jefe en la sección, tenga o no fila en `acceso_seccion`.
  2. Si no, vale su fila de `acceso_seccion`.
  3. Si no tiene fila, no tiene acceso. Los otros roles de grupo (Supervisor, Ayudante, Responsable) no pasan a las secciones.
- **En un grupo:** vale solo la fila de `acceso_grupo`. Se usa para las pantallas del grupo: estadísticas, listado de secciones y eventos grupales.
- **El admin** no tiene acceso a ningún grupo por ser admin. `exigirAdmin` solo se usa para aprobar o rechazar solicitudes de alta.

| Método | Para qué |
| --- | --- |
| `nivelEnSeccion`, `nivelEnGrupo` | Devuelven el `NivelRol`, o `null` si no hay acceso |
| `puedeEnSeccion`, `puedeEnGrupo` | Devuelven `bool`, para mostrar u ocultar botones en las vistas |
| `exigirEnSeccion`, `exigirEnGrupo` | Lanzan `AccesoDenegadoException` si no llega al rol mínimo; si llega, devuelven el rol |
| `esJefeDeGrupo`, `exigirAdmin` | Casos puntuales |

Uso típico dentro de otro Service, antes de escribir:

```php
$this->permisos->exigirEnSeccion($idUsuario, $idSeccion, NivelRol::Responsable);
```

Se probó todo contra `educarama_v2` dentro de una transacción que después se deshizo:

- Un Jefe de grupo es Jefe en sus secciones aunque tenga una fila de Ayudante, y no tiene acceso a las de otro grupo.
- Un Supervisor de grupo no tiene acceso a una sección donde no tiene fila, y es Responsable donde tiene fila de Responsable.
- El admin no tiene acceso a ninguna sección.
- Si la sección no existe, se lanza `NoEncontradoException`.
- `transaccion` hace rollback ante una excepción, y dentro de una transacción ya abierta no hace commit.

## Cupo fijo de competencias por etapa

**Qué cambió.** En la tabla `etapa` se borraron las columnas `min_competencias`, `max_competencias`, `pct_desde` y `pct_hasta`. En su lugar hay una sola columna, `competencias_etapa INT NOT NULL`: el cupo fijo de competencias de cada etapa. El Model `Etapa` cambió igual: ahora tiene `$competenciasEtapa` en vez de las cuatro propiedades viejas.

**Por qué (decisión de diseño).** El programa de Lobatos y Lobeznas pide "16 a 20 competencias por etapa", pero el catálogo de LyL tiene 47 competencias. Como cada competencia se elige una sola vez (la PK de `competencia_protagonista` es protagonista + competencia), cuatro etapas de 16 necesitarían 64, y no alcanzan. Contar el acumulado tampoco servía, porque todas las etapas tenían el mismo mínimo. Por eso se tomó un esquema único para las cuatro ramas: cada rama reparte su total de competencias entre sus etapas. Caminantes deja de usar porcentajes, y Rovers, que no tenía regla, pasa a tener cupo.

**Cómo se calcula el cupo.** Es el total de la rama dividido por la cantidad de etapas, redondeado. La última etapa se lleva el resto, así los cupos de cada rama suman exactamente su total:

| Rama | Total | Etapa 1 | Etapa 2 | Etapa 3 | Etapa 4 |
| --- | --- | --- | --- | --- | --- |
| Lobatos y Lobeznas | 47 | 12 | 12 | 12 | 11 |
| Scouts | 53 | 13 | 13 | 13 | 14 |
| Caminantes | 49 | 12 | 12 | 12 | 13 |
| Rovers (3 etapas) | 37 | 12 | 12 | 13 | — |

Es un dato estático en la base, no una constante de PHP ni un cálculo en vivo. Si cambia el catálogo, el dev actualiza la columna.

**Cómo funciona.**

- Las competencias se cuentan por etapa: las que tienen `id_etapa` igual a esa etapa, comparadas con su cupo. Al elegir una competencia, su `id_etapa` toma la etapa en curso, que es la siguiente a la última lograda.
- Hay dos avisos, y los dos se pueden saltear. Al elegir: si la etapa en curso ya llegó a su cupo, se avisa que se está eligiendo de más. Al completar: cuando las desarrolladas de la etapa en curso llegan al cupo, se sugiere confirmar la etapa.
- En la misma conversación se cambió la regla de las insignias. Ya no se ofrecen al confirmar una etapa: se registran a mano, con la fecha de la entrega física. Solo hay un aviso cuando una etapa empieza y el protagonista todavía no recibió su insignia. Las reglas completas de progresión están en CLAUDE.md, en "Progresión" y "Pantalla de competencias del protagonista".

**Base ya importada.** Para aplicarlo sin reimportar, hay un script de migración que agrega la columna, carga los cupos, la pasa a NOT NULL, borra las cuatro columnas viejas y controla que los cupos de cada rama sumen su total de competencias. Se probó sobre una copia de `educarama_v2`.

## ProgresionService

Es el Service de la progresión personal: competencias, acciones, tareas de etapa, etapas, especialidades e insignias. Aplica las reglas de "Progresión" de CLAUDE.md.

**Cómo avisa.** Hay dos tipos de mensaje, y los dos usan la clase `Aviso` (un tipo `TipoAviso` y un mensaje listo para mostrar):

- **Requisitos que se pueden saltear** (cupo excedido, acciones requeridas sin cumplir, cupo incompleto o tareas al confirmar una etapa). El Service no guarda nada y lanza `RequisitosPendientesException`, que trae los avisos en `$e->avisos`. La vista los muestra y pregunta "¿Registrar igual?". Si el educador acepta, se repite la misma llamada con `$confirmarIgual = true`. Así nadie puede registrar algo sin haber visto el aviso.
- **Sugerencias** ("¿dar la competencia por desarrollada?", "¿confirmar la etapa?"). Llegan después de guardar, dentro del `ResultadoProgresion` que devuelve la operación, y no frenan nada. Ese objeto tiene solo propiedades públicas, así que el Controller lo puede mandar al fetch con `json_encode`.

Lo único que bloquea siempre, aunque se pase `$confirmarIgual`, es confirmar una etapa sin tener lograda la anterior. Esto, igual que las validaciones de rama, lanza `ReglaNegocioException`.

**Etapa actual y etapa en curso.** La etapa actual es la última lograda en la rama de la sección. La etapa en curso es la siguiente, o la primera si no logró ninguna. Las dos se calculan en vivo en cada llamada.

**Operaciones** (todas reciben primero el `$idUsuario`; la fecha es opcional y, si no se pasa, se usa la de hoy):

| Método | Rol | Qué hace |
| --- | --- | --- |
| `resumen` | Ayudante | Devuelve un `ResumenProgresion`: etapa actual y en curso, contadores (de la etapa, total y por área), lo adeudado y la insignia pendiente |
| `avanzarCompetencia` | Responsable | Pendiente → En proceso (la asigna a la etapa en curso; avisa si se pasa del cupo) → Desarrollada (avisa si faltan acciones; sugiere confirmar la etapa) |
| `retrocederCompetencia` | Responsable | Desarrollada → En proceso → Pendiente (borra la fila y conserva las acciones) |
| `marcarAccion`, `desmarcarAccion` | Responsable | Exigen que la competencia esté elegida; al cumplir la última acción requerida, sugiere darla por desarrollada |
| `marcarTarea`, `desmarcarTarea` | Responsable | Tareas de etapa |
| `confirmarEtapa` | Responsable | Avisa por el cupo y las tareas; bloquea si falta la etapa anterior; en Caminantes exige el elemento y en las demás ramas lo rechaza |
| `quitarEtapa` | Jefe | Solo la última etapa lograda |
| `otorgarInsignia` | Responsable | Registro manual, con la fecha de la entrega física |
| `quitarInsignia` | Jefe | |
| `avanzarEspecialidad`, `retrocederEspecialidad` | Responsable | Igual que las competencias, pero sin avisos; solo en ramas con `usa_especialidades` |

**Detalles de implementación.**

- Las operaciones que marcan o desmarcan (acciones y tareas) no fallan si se repiten: si ya estaba tildada, no hacen nada. Así, si el guardado inmediato manda dos veces el mismo pedido, no da error.
- "Acción requerida" significa `es_opcional = 0`. En Scouts y Caminantes todas sus acciones tienen 0, así que la misma regla sirve para todas las ramas sin preguntar cuál es.
- Caminantes se reconoce por su id (3, de "Datos oficiales"), porque la tabla `rama` no tiene una columna que indique si la rama usa elemento. Es la constante `ID_RAMA_CAMINANTES`.
- La rama sale siempre por ids: protagonista → sección → rama. Los contadores recorren el catálogo de la rama actual, así no se cuentan competencias de una rama anterior.
- Las clases que devuelven los Services y que no son Models van en `src/Service/Vista/`: `Aviso`, `TipoAviso`, `ResultadoProgresion`, `ResumenProgresion` y `ContadorArea`.
- A `TareaEtapaRepository` se le agregó `buscarPorId`.

Se probó todo contra `educarama_v2` dentro de una transacción que después se deshizo, con 49 casos:

- permisos de Ayudante, Responsable y Jefe de grupo;
- el ciclo completo de una competencia con sus acciones y la sugerencia al cumplir la última;
- llenar el cupo de Pata Tierna, la sugerencia de confirmar y el aviso al elegir la competencia 13;
- el bloqueo por la etapa anterior, lo adeudado de la etapa 2 (cupo y tarea) y la confirmación igual;
- quitar solo la última etapa, y el retroceso que conserva las acciones;
- el elemento en Caminantes y Rovers sin acciones ni especialidades;
- las insignias con su aviso, y las fechas inválidas.

## ProtagonistaService y PatrullaService

**Cómo reciben los datos.** El Controller lee `$_POST`, convierte los tipos y arma los Models: `Persona` y `Protagonista`, o `Patrulla`. El Service los valida, limpia los espacios de los textos y los guarda. En el alta, `$protagonista->idPersona` se ignora: el Controller puede pasar `0`, y el Service lo completa con la persona recién creada.

**Los ids salen de la base.** En `actualizar`, el `$protagonista->id` indica a quién se edita, pero la persona y la sección se toman de lo guardado. Así un formulario adulterado (con otro `id_persona` en un campo oculto) no puede editar a otra persona. Si la sección del objeto es distinta de la guardada, se rechaza: para eso está `cambiarSeccion`.

**`ProtagonistaService`**

| Método | Rol | Qué hace |
| --- | --- | --- |
| `listarPorSeccion`, `buscarFicha` | Ayudante | Devuelven `FichaProtagonista`. El Supervisor no ve datos personales |
| `alta` | Responsable | Crea la persona y el protagonista en una transacción |
| `actualizar` | Responsable | Datos personales, patrulla, promesa y año de ingreso |
| `cambiarSeccion` | Responsable en las dos secciones | Solo dentro del mismo grupo. Queda sin patrulla; la progresión anterior queda como historial |
| `eliminar` | Jefe | Cascada en la base; la persona queda |

Validaciones:

- DNI (entero positivo), nombre y apellido son obligatorios.
- La fecha de nacimiento es opcional, pero si viene tiene que ser una fecha real.
- El año de ingreso, si se carga, no puede ser futuro.
- El DNI es único en `persona`. Si ya existe, se rechaza, aunque sea de un familiar.
- La patrulla tiene que ser de la misma sección.

**`PatrullaService`**

| Método | Rol | Qué hace |
| --- | --- | --- |
| `listarPorSeccion`, `buscar`, `integrantes` | Ayudante | |
| `crear`, `renombrar` | Responsable | El nombre no se repite en la sección |
| `asignarIntegrante` | Responsable | `null` lo saca de la patrulla. Es lo que necesita la pantalla de la patrulla (el legacy enlazaba a un archivo inexistente) |
| `eliminar` | Jefe | Los integrantes quedan sin patrulla (`ON DELETE SET NULL`) |

`exigirDeSeccion($idPatrulla, $idSeccion)` es público porque `ProtagonistaService` lo usa para validar la patrulla. Así la regla está escrita una sola vez. El nombre repetido se busca con la intercalación de la base (`utf8mb4_spanish_ci`), que no distingue mayúsculas ni acentos: "Ñandú" y "ñandu" cuentan como el mismo nombre.

**Primera clase vista de un repositorio: `FichaProtagonista`.** Está en `src/Repository/Vista/` y contiene el `Protagonista`, la `Persona` y el nombre de la patrulla. Sale de un JOIN (protagonista + persona + patrulla), así un listado de sección se resuelve en una sola consulta. Las columnas de persona y patrulla vienen con alias (`persona_nombre`, `patrulla_nombre`), porque `id` y `nombre` se repiten entre tablas. Con esto deja de valer la regla anterior "sin clases vista por ahora". Las clases vista de los repositorios van en `src/Repository/Vista/`, y las que arman los Services, en `src/Service/Vista/`.

**Otros cambios.**

- `ProtagonistaRepository`: se agregaron `buscarPorPersona`, `buscarFicha`, `listarFichasPorSeccion` y `listarFichasPorPatrulla`.
- `PatrullaRepository`: se agregó `buscarPorNombre`.
- `ServicioBase`: se agregó `exigirFecha()`, que controla que una fecha sea real y esté en formato `AAAA-MM-DD`. Antes estaba solo en `ProgresionService`; ahora la usan los dos Services.

Se probó todo contra `educarama_v2` dentro de una transacción que después se deshizo, con 41 casos. Además se repitió la prueba de `ProgresionService`, y sus 49 casos siguen pasando.

- Patrullas: nombre repetido (también con otras mayúsculas o sin acentos), vacío y renombrar a su mismo nombre.
- Alta: DNI repetido, también el de un familiar; patrulla de otra sección; y fechas y años inválidos.
- Listado ordenado por apellido, con los tipos bien convertidos.
- Edición con ids adulterados: edita la persona correcta.
- Pase: entre grupos se rechaza; dentro del grupo, el protagonista queda sin patrulla.
- Borrado de una patrulla con integrantes, y de un protagonista: su persona queda.

## Almacenamiento de archivos

Los documentos de los protagonistas (fichas médicas, autorizaciones) son datos de menores. Se guardan en un **almacén** que no se puede abrir por URL, y solo se descargan después de controlar quién los pide.

### Por qué hay dos capas

| Capa | Dónde | Qué sabe | Qué no sabe |
| --- | --- | --- | --- |
| Componente genérico | `src/Almacen/` (`Educarama\Almacen`) | Guardar un archivo subido, enviarlo al navegador y borrarlo, de forma segura | Qué es un protagonista, la base de datos o los permisos |
| Uso en Educarama | `DocumentoService` | Quién puede ver o subir, en qué subcarpeta va cada protagonista y la tabla `documento` | Cómo se valida un archivo o qué encabezados HTTP hacen falta |

La seguridad de los archivos (validar el tipo, generar nombres, impedir `../`) es difícil de hacer bien y es la misma en cualquier proyecto. Por eso está separada y no depende de nada de Educarama: no usa Models, Repositories, Services ni la base. Así se puede llevar a otro proyecto, y un cambio en las reglas de Educarama no la puede romper. La regla quedó en CLAUDE.md: al componente no se le agrega nada específico de Educarama.

### El componente (`src/Almacen/`)

- `AlmacenArchivos`:
  - `guardar($_FILES['x'], $subcarpeta)` valida y guarda el archivo, y devuelve un `ArchivoGuardado` con la ruta relativa, el nombre original, el tipo MIME y el tamaño.
  - `enviar($ruta, $nombre, $enLinea)` manda el archivo al navegador.
  - `borrar($ruta)` y `borrarCarpeta($subcarpeta)` borran del disco.
  - `verificarLimiteDelServidor()` detecta cuando el pedido superó `post_max_size` (ver más abajo).
- `ArchivoInvalidoException`: su mensaje es para el usuario ("El archivo supera los 5 MB.", "Solo se aceptan archivos PDF, JPG o PNG."). Los errores del servidor (no se pudo crear la carpeta, disco lleno) son `\RuntimeException`, y su mensaje no se le muestra al usuario.
- **Configuración** en `config/config.php`, con valores por defecto:
  - `ALMACEN_RUTA`: la carpeta del almacén. Por defecto, `storage/` en la raíz del proyecto.
  - `ALMACEN_TAMANIO_MAXIMO`: 5 MB.
  - `ALMACEN_TIPOS_PERMITIDOS`: tipo MIME => extensión (PDF, JPG y PNG).

  Para sacar el almacén de `htdocs`, alcanza con definir otra `ALMACEN_RUTA` en `config.local.php`.

### Cómo se protege

- **Tipo real.** El tipo se detecta leyendo el contenido con `finfo`, nunca por la extensión ni por el tipo que manda el navegador. Un PHP renombrado `foto.jpg` se rechaza. El archivo se guarda con la extensión que corresponde a su tipo real.
- **Nombre aleatorio.** En el disco, cada archivo se llama `bin2hex(random_bytes(16))` + extensión: no se puede adivinar y no usa nada de lo que escribió el usuario. El nombre original se guarda en la base solo para mostrarlo y para la descarga. Antes se limpia: se le sacan carpetas, comillas y saltos de línea, y si llega en Windows-1252 se convierte a UTF-8.
- **Solo subidas reales.** `is_uploaded_file` y `move_uploaded_file` impiden "guardar" un archivo cualquiera del servidor.
- **Rutas encerradas.** Cada parte de una ruta solo acepta letras, números, punto, guion y guion bajo, y no puede ser `.` ni `..`. Para lo que ya existe, además se compara su `realpath` con la raíz del almacén, así tampoco pasa un enlace simbólico que apunte afuera.
- **Descarga.** `enviar()` vuelve a leer el tipo del contenido y manda estos encabezados:
  - `Content-Type`;
  - `Content-Disposition`, con el nombre en ASCII y en UTF-8 (`filename*`);
  - `X-Content-Type-Options: nosniff`;
  - `Cache-Control: private, no-store`.

  Solo las imágenes se muestran en línea; un PDF siempre se descarga como adjunto, aunque se pida en línea.
- **`post_max_size`.** Si un pedido lo supera, PHP deja `$_POST` y `$_FILES` vacíos sin avisar. `verificarLimiteDelServidor()` lo detecta y muestra el mismo mensaje de "supera el tamaño máximo".
- **`storage/`.** Tiene un `.htaccess` con `Require all denied`, así que Apache no lo sirve. En `.gitignore` está todo su contenido salvo ese `.htaccess`. El `.htaccess` **solo funciona en Apache**: en otro servidor, `ALMACEN_RUTA` tiene que estar fuera de la carpeta pública.

### Cómo se reutiliza

Está explicado en la cabecera de `AlmacenArchivos.php`:

1. Copiar la carpeta `src/Almacen/` completa.
2. Cambiar el namespace (o registrarlo en el autoload del otro proyecto).
3. Definir las constantes `ALMACEN_*`, o pasar los valores al constructor.
4. Proteger la carpeta: con `.htaccess` en Apache, o poniéndola fuera de la carpeta pública en otro servidor.

### Uso en Educarama: `DocumentoService`

- **Columnas nuevas.** La tabla `documento` sumó `tipo_mime VARCHAR(100) NOT NULL` y `tamanio_bytes INT NOT NULL`. `ruta_archivo` es relativa al almacén (por ejemplo, `documentos/protagonista/7/3f9a…e1.pdf`) y nunca se le muestra al usuario. Hay un script de migración para la base ya importada.
- **Subcarpeta.** Cada protagonista tiene la suya, `documentos/protagonista/{id}/`. La regla está en un solo lugar, `DocumentoService::carpetaDe()`, y también la usa `ProtagonistaService`.
- **Permisos:**
  - ver y descargar, Ayudante;
  - subir y editar el tipo y la descripción, Responsable;
  - eliminar, Jefe.
- **Pertenencia.** Cada operación recorre documento → protagonista → sección y controla el acceso del usuario a esa sección; el Jefe de grupo entra por derivación. Si el usuario no tiene ningún acceso a esa sección, la respuesta es **404**, con el mismo mensaje que un documento inexistente, así no se confirma que existe. Si tiene acceso pero le falta el rol, es 403. Esto lo controla el Service (no el Controller), como el resto de los permisos, así ningún Controller se lo puede olvidar.
- **Orden de las operaciones:**
  - Subir: primero se guarda el archivo y después se inserta la fila. Si el insert falla, se borra el archivo, así no quedan archivos sin fila.
  - Eliminar: primero se borra la fila y después el archivo. Si borrar el archivo falla, se anota en el log y no se le muestra error al usuario: el archivo quedó sin fila, así que nadie lo puede pedir.
  - Eliminar un protagonista: la base borra sus filas de `documento` en cascada, y `ProtagonistaService::eliminar` borra después su carpeta del almacén.
- **Excepciones.** `ArchivoInvalidoException` se traduce a `ReglaNegocioException` con el mismo mensaje. Así el Controller sigue atrapando una sola familia de excepciones (`ServicioException`).
- **La descarga, en el Controller (paso 4):**

  ```php
  $documento = $documentoService->buscar($idUsuario, $idDocumento);  // controla la pertenencia
  $almacen->enviar($documento->rutaArchivo, $documento->nombreOriginal, enLinea: true);
  ```

### CSRF: `src/Helper/Csrf.php`

Protege contra que otra página, abierta en el mismo navegador, mande un formulario a Educarama en nombre del usuario. Cada sesión tiene un token aleatorio:

- En la vista, cada formulario POST lo manda en un campo oculto: `<?= Csrf::campo() ?>`.
- En el Controller, antes de procesar el POST, se compara con el de la sesión: `Csrf::esValido($_POST[Csrf::CAMPO] ?? null)`. La comparación usa `hash_equals`, que tarda lo mismo acierte o no.
- `Csrf::regenerar()` se va a llamar al iniciar y al cerrar sesión.

CLAUDE.md lo deja como regla: todo formulario POST nuevo lo usa. Se va a aplicar en los formularios de subida y de borrado cuando se hagan las vistas.

### Prueba manual en XAMPP (el bloqueo de `storage/`)

El servidor de `php -S` no lee `.htaccess`, así que esto no se pudo probar automáticamente. Hay que probarlo en XAMPP (Apache):

1. Copiar cualquier archivo a `storage/`, por ejemplo `storage/prueba.txt`. Cuando existan las vistas, se puede subir un documento desde la aplicación y ver su `ruta_archivo` en la tabla `documento`.
2. Abrir en el navegador `http://localhost/Educarama/storage/prueba.txt` (o `http://localhost/Educarama/storage/` + la `ruta_archivo`).
3. **Tiene que dar 403 Forbidden.** Si se ve el archivo, Apache no está leyendo el `.htaccess`: revisar que `AllowOverride` no esté en `None` para `htdocs` en `httpd.conf`.
4. Borrar el archivo de prueba.

## FamiliarService y validaciones compartidas

**`FamiliarService`.** Los familiares se gestionan siempre desde un protagonista: el permiso se controla en la sección de ese protagonista, y el familiar tiene que estar vinculado a él. Un mismo familiar (por ejemplo, la madre de dos hermanos) es una sola fila en `familiar`, vinculada a cada protagonista por `es_familia`. Para mostrarlo se usa la clase vista `FichaFamiliar` (familiar + persona + vínculo), que sale de un JOIN.

| Método | Rol | Qué hace |
| --- | --- | --- |
| `listarPorProtagonista`, `buscarFicha` | Ayudante | Primero los contactos de emergencia |
| `alta` | Responsable | Crea persona, familiar y vínculo. Si el DNI ya existe, avisa y deja confirmar (ver abajo) |
| `actualizar` | Responsable | Datos personales, contacto y el vínculo con ese protagonista. Los ids salen de la base |
| `desvincular` | Responsable | Si el familiar queda sin protagonistas, avisa y deja confirmar (ver abajo) |

- **DNI ya cargado.** El aviso (`persona_existente`) muestra solo el nombre, nunca a qué otros protagonistas está vinculada, porque pueden ser de otro grupo. Si se confirma con `$confirmarIgual`, se crea solo el vínculo y no se pisan los datos guardados. Si la persona existe pero no era familiar (por ejemplo, un hermano Rover), se crea su ficha de familiar sobre esa misma persona. El DNI del propio protagonista se rechaza.
- **Desvincular el último vínculo.** El aviso (`familiar_huerfano`) dice "Ana Pérez no queda vinculada a ningún protagonista". Solo si se confirma se borra su fila de `familiar`. La persona queda, como cuando se borra un protagonista.
- **Validaciones.** DNI, nombre y apellido son obligatorios. El teléfono es texto libre de hasta 30 caracteres. El email, si viene, tiene que ser válido. El vínculo admite hasta 50 caracteres. Los textos vacíos se guardan como `NULL`.

**`Validacion`** (`src/Service/Validacion.php`). Junta las validaciones que comparten varios Services y que no consultan la base: `fecha`, `persona`, `textoOpcional` (con el largo de la columna) y `emailOpcional`. `ServicioBase::exigirFecha()` se reemplazó por `Validacion::fecha()`, y `ProtagonistaService` pasó a usar `Validacion::persona()`. Así las reglas de una persona son las mismas para un protagonista y para un familiar.

**Textos que no son UTF-8.** `Validacion` rechaza cualquier texto que no sea UTF-8 válido, con el mensaje "… tiene caracteres no válidos". Los navegadores mandan los formularios en UTF-8. Si llega otra codificación (en las pruebas pasó con `curl` en Windows, que manda Windows-1252), MySQL guardaba "Ficha m?dica" sin avisar. Es mejor rechazarlo que guardar el texto alterado.

**Pruebas.** Se probó todo contra `educarama_v2` o contra una copia que después se borró. Las subidas fueron HTTP reales, con el servidor de PHP (`php -S`) y `curl`.

| Prueba | Casos | Qué cubre |
| --- | --- | --- |
| Almacén | 28 | Tipo real contra lo que dice el navegador, PHP disfrazado de JPG, vacío, más de 5 MB, `post_max_size`, archivo que no llegó por HTTP, `../` y rutas absolutas, encabezados de descarga, nombre con comillas o saltos de línea, borrar |
| `DocumentoService` | 25 | Permisos (403 con acceso a la sección, 404 sin acceso, con el mismo mensaje que un documento inexistente), Jefe de grupo por derivación, insert que falla sin dejar el archivo, texto que no es UTF-8, borrar la carpeta al eliminar un protagonista |
| `FamiliarService` | 27 | DNI ya cargado (aviso, reutilización, datos no pisados), vínculo de cada protagonista, ids adulterados, desvincular con y sin aviso |
| Repetidas | 41 + 49 | `ProtagonistaService` y `ProgresionService`, después de los cambios en `Validacion` |

El script de migración también se probó sobre una copia a la que antes se le quitaron las columnas. El bloqueo de `storage/` por `.htaccess` queda para la prueba manual en XAMPP de más arriba.

## UsuarioService y SolicitudAltaService

### Cuentas y sesión: `UsuarioService`

**Es el único Service que toca `$_SESSION`.** Así se decidió para el login. Todos los demás Services siguen recibiendo el `$idUsuario` como parámetro. El Controller lo obtiene de acá:

```php
$usuario = $usuarioService->exigirSesion();   // sin sesión: SesionRequeridaException → al login
$protagonistaService->alta($usuario->id, $persona, $protagonista);
```

En la sesión se guarda **solo el id** del usuario. Sus datos (nombre, si es admin) se vuelven a leer de la base en cada pedido, así un cambio se nota enseguida, y si la cuenta se borra, la sesión deja de valer. Las vistas reciben un `UsuarioSesion` (id, nombre, email, esAdmin), nunca el `Usuario` con el hash.

| Método | Qué hace |
| --- | --- |
| `registrar` | Crea la cuenta sin accesos y deja al usuario logueado |
| `iniciarSesion` | Verifica el email y la contraseña, y abre la sesión |
| `cerrarSesion` | Borra los datos de la sesión, la cookie y la sesión del servidor |
| `usuarioActual` / `exigirSesion` | El usuario logueado, o `null` / la excepción |
| `cambiarContrasenia` | Pide la contraseña actual. Al terminar renueva el id de sesión |

**Seguridad del login.**

- **Mensaje único.** Con un email inexistente y con una contraseña incorrecta, el mensaje es el mismo: "Email o contraseña incorrectos." El legacy los distinguía, y eso permitía averiguar qué emails están registrados.
- **Mismo tiempo de respuesta.** Si el email no existe, igual se compara la contraseña contra un hash ficticio, así el login tarda lo mismo y tampoco se puede averiguar midiendo tiempos.
- **Renovación al loguearse.** Se renuevan el id de sesión (`session_regenerate_id`) y el token CSRF. Un id de sesión que alguien conociera de antes deja de servir.
- **Hashes viejos.** `password_needs_rehash` actualiza el hash si PHP cambió el algoritmo o el costo por defecto.
- **Email.** Se guarda sin espacios y en minúsculas, así no hay dos cuentas "Ana@…" y "ana@…".

**`src/Helper/Sesion.php`.** Arranca siempre la sesión con la misma configuración:

- cookie `httponly`, así el JavaScript de la página no la puede leer;
- `SameSite=Lax`;
- `secure` si la página se sirve por HTTPS;
- `use_strict_mode`, así PHP no acepta un id de sesión inventado por el cliente.

`Csrf` también lo usa. Nunca se llama a `session_start()` suelto.

**Contraseña: dos versiones.** Está activa la **versión B**, con un mínimo de 6 caracteres, porque en las pruebas se usan contraseñas cortas. La **versión A**, para producción, está comentada en `UsuarioService::validarContrasenia()`: mínimo 8 caracteres y máximo 72 bytes, porque bcrypt ignora lo que pasa de 72 bytes. Para activarla, se borra el bloque de la B y se descomenta el de la A.

**Quedan para más adelante:**

- **Recuperar la contraseña.** Hace falta envío de emails; la columna `token_recuperacion` queda reservada. El legacy mostraba el código en pantalla, así que cualquiera que supiera un email podía cambiar esa contraseña.
- **Bloqueo por intentos fallidos.** No hay dónde contarlos.

### Alta de grupos: `SolicitudAltaService`

El circuito es: el usuario se registra → envía una solicitud → un admin la aprueba → se crean el grupo y sus secciones, y el usuario queda como Jefe del grupo.

- **Contenido.**
  - `datosGrupo` lleva `{codigo, nombre}`, las columnas de `grupo`.
  - `datosSecciones` es una lista de `{nombre, id_rama}`, las columnas de `seccion`.
  - Se guardan limpios: sin espacios de más y solo con esas claves. Lo que venga de más en el formulario se descarta.
- **Validaciones.** Se hacen al enviar y otra vez al aprobar, porque mientras tanto otro grupo pudo tomar el código:
  - el código no tiene que estar usado;
  - tiene que haber al menos una sección;
  - las ramas tienen que existir;
  - no se repiten nombres de sección, aunque cambien las mayúsculas.
- **Una pendiente por usuario.** Si se la rechazan, puede enviar otra. La solicitud no se edita.
- **Aprobar** (solo admin). En una sola transacción crea el grupo, las secciones y el `acceso_grupo` del solicitante con el rol Jefe. Con eso queda Jefe de todas las secciones por derivación, sin filas en `acceso_seccion`. Si la validación falla, no se crea nada.
- **Rechazar** (solo admin). Exige un motivo, que se guarda en la columna nueva `solicitud_alta.motivo_rechazo` y que el solicitante ve en `misSolicitudes`. El aviso por email está preparado en `notificarRechazo()`, pero todavía no hace nada.
- **Ver una solicitud.** La ven su dueño y los admins. A cualquier otro usuario se le responde "no existe".
- **Cruz del Sur.** La validación queda para más adelante; `email_validado` sigue siempre en `false`. Por eso no se creó la interfaz `ValidadorCruzDelSur`.

Para la base ya importada hay un script de migración que agrega `motivo_rechazo`.

**Pruebas.** Se probó todo con 46 casos sobre una copia de `educarama_v2` con la migración aplicada, dentro de una transacción que después se deshizo.

- **Cuentas:** registro, login, logout, el mensaje único, la renovación del id de sesión y del token CSRF, la actualización de un hash viejo, una cuenta borrada y el cambio de contraseña.
- **Solicitudes:** todas las validaciones, la aprobación (grupo, secciones y el Jefe por derivación), el código tomado entre el envío y la aprobación (no crea nada) y el rechazo con motivo.

Además se repitieron las pruebas anteriores: progresión (49), protagonistas (41), familiares (27) y documentos (25).

## AccesoService

Gestiona quién tiene acceso a un grupo o a una sección, y con qué rol. Las reglas quedaron en CLAUDE.md, en "Accesos".

**Cómo se suma a alguien.** El Jefe escribe el email de una cuenta **ya registrada**. Si no hay ninguna cuenta con ese email, el mensaje es "No hay una cuenta con ese email: pedile que se registre en Educarama". La idea es que más adelante se pueda invitar por email, pero hoy no hay envío de emails (quedó anotado en "Para más adelante"). El email se busca sin importar mayúsculas, igual que en el login.

| Método | Quién | Qué hace |
| --- | --- | --- |
| `listarAccesosGrupo` | Jefe de grupo | Nombre, email y rol de cada usuario con acceso al grupo |
| `darAccesoGrupo`, `cambiarRolGrupo`, `quitarAccesoGrupo` | Jefe de grupo | Cualquier rol. No deja al grupo sin Jefe |
| `listarAccesosSeccion` | Jefe de la sección o Jefe de grupo | Los asignados a la sección (los Jefes de grupo no figuran, porque no tienen fila) |
| `darAccesoSeccion`, `quitarAccesoSeccion` | Jefe de grupo | |
| `cambiarRolSeccion` | Jefe de grupo: cualquier rol. Jefe de sección: hasta Responsable | El Jefe de sección no puede tocar a otro Jefe ni a sí mismo |

**El último Jefe del grupo.** Antes de quitar o bajar de rol a un Jefe de grupo, se cuentan los Jefes del grupo. Si es el único, la operación se rechaza con "Es el único Jefe del grupo: primero nombrá a otro Jefe", aunque lo intente él mismo. Así un grupo nunca se queda sin nadie que lo administre.

**Quitar el acceso de grupo** no borra las filas de sección que tuviera esa persona. Son accesos aparte, y se quitan desde cada sección.

**La clase vista `UsuarioConAcceso`** (en `src/Repository/Vista/`) trae solo el id, el nombre, el email y el `NivelRol`. Sale de un JOIN entre el acceso, `usuario` y `rol`, así la lista nunca carga el hash de la contraseña. Los repositorios de acceso suman `listarUsuariosPorGrupo` y `listarUsuariosPorSeccion`, que ordenan primero por el rol más alto y después por nombre.

**Pruebas.** Se probó todo con 35 casos contra `educarama_v2`, dentro de una transacción que después se deshizo:

- permisos de cada rol, incluido el Jefe de otro grupo;
- dar acceso por email (sin importar mayúsculas, sin cuenta, repetido);
- el último Jefe, también cuando se intenta bajar a sí mismo;
- los límites del Jefe de sección (hasta Responsable, sin tocar a otros Jefes, sin agregar ni quitar);
- que la lista no traiga el hash.

## EventoService

Es el último Service. Maneja los eventos de un grupo, que pueden ser de todo el grupo o de algunas de sus secciones. Las reglas quedaron en CLAUDE.md, en "Eventos".

**Un evento sin secciones es de todo el grupo.** Al crear o editar, el Controller pasa la lista completa de secciones elegidas. Vacía significa todo el grupo. El Service saca los repetidos y controla que cada sección exista y sea del grupo del evento. Al editar, borra las filas de `evento_seccion` e inserta las nuevas, todo dentro de una transacción: si algo falla, el evento queda como estaba.

**Quién escribe.** Depende de a quién le toca el evento:

| El evento es de… | Crear, editar, desactivar, reactivar | Eliminar |
| --- | --- | --- |
| Todo el grupo | Responsable de grupo (o Jefe de grupo) | Jefe de grupo |
| Algunas secciones | Responsable en **cada** sección | Jefe en cada sección |

El Jefe de grupo es Jefe de todas sus secciones por derivación, así que puede todo. Un Responsable de grupo **no** puede crear eventos de una sección: los roles de grupo no se heredan. Al editar se pide el permiso dos veces, sobre el evento como está y como queda. Así, pasar un evento de la Manada a todo el grupo pide ser Responsable en la Manada **y** en el grupo.

**Quién ve.** El Service calcula el rol más alto del usuario entre su rol de grupo y su rol en cada sección a la que le toca el evento (todas las del grupo, si es de todo el grupo). Con ese rol decide:

- sin ningún rol, 404 (no se confirma que el evento existe, igual que con los documentos);
- Supervisor: solo los que tienen `es_publico`;
- inactivos: solo Responsable en adelante.

Los listados son dos: `listarPorGrupo` es la pantalla del grupo y pide un rol de grupo; `listarPorSeccion` es la de la sección y pide acceso a esa sección. Por defecto traen solo los activos; con `$incluirInactivos = true` traen todos, y eso es de Responsable.

**Próximos eventos.** `proximosPorGrupo` y `proximosPorSeccion` traen los activos que todavía no terminaron: su `fecha_fin`, o su `fecha_inicio` si no tiene fin, es de hoy en adelante. Un campamento que empezó ayer y termina mañana sigue apareciendo.

**Fechas.** No se crea un evento que empieza antes de hoy. Al editar, si la fecha de inicio no cambia, puede quedar en el pasado: un evento que ya empezó se sigue pudiendo corregir. Para calcular "hoy", `config.php` define `ZONA_HORARIA` (America/Argentina/Buenos_Aires). PHP en XAMPP viene con Europe/Berlin, y con esa zona el día cambiaba unas horas antes.

**La clase vista `EventoConSecciones`** (en `src/Service/Vista/`) junta el evento y los ids de sus secciones, para los listados y el formulario de edición. Para no hacer una consulta por evento, `EventoSeccionRepository::listarPorGrupo` trae en una sola consulta las filas de todos los eventos del grupo.

**Lo que no cambia por `actualizar`:** el grupo, `activo` (para eso están `desactivar` y `reactivar`) y `id_tipo_evento`, que queda siempre en NULL porque está reservado para las plantillas.

**Pruebas.** Se probó todo con 60 casos contra `educarama_v2`, dentro de una transacción que después se deshizo:

- permisos de cada rol, en el grupo y en las secciones, incluido el Jefe de otro grupo;
- secciones de otro grupo, inexistentes y repetidas;
- validaciones de título, descripción y fechas, también al editar un evento que ya empezó;
- lo que ve el Supervisor, los inactivos y el 404;
- reemplazar la lista de secciones al editar;
- los próximos eventos (terminados, en curso, que terminan hoy, inactivos).

## Controllers: infraestructura y UsuarioController

Empieza el paso 4. Antes de los Controllers de cada tema hacía falta decidir cómo entra un pedido y cómo sale la respuesta. Las reglas cortas quedaron en CLAUDE.md, en "Controllers y pedidos".

**Dos puertas de entrada.**

- **Páginas** (`views/pages/*.php`): solo muestran (GET). Cada una llama a su Controller a través de `Pagina::cargar()` y usa los datos que vuelven para armar el HTML. Si no hay sesión, `Pagina` manda al login con `?volver=` la página pedida; si el Service dice 403 o 404, muestra `views/includes/error.php`.
- **`public/api.php`**: todo lo que guarda (también el login y el registro) va por fetch a `api.php?accion=tema.metodo`. El archivo tiene la **lista fija** de acciones: el navegador elige una de esas, nunca el nombre de un método. `Educarama\Controller\Api` busca la acción, controla el método HTTP y el token CSRF, llama al Controller y traduce las excepciones.

**`Respuesta`.** Un Controller no hace `echo` ni manda headers: devuelve una `Respuesta` (página con datos, JSON, redirección o archivo del almacén) y la emite `Api` o `Pagina`. Así se puede probar por CLI llamando al método con arrays armados a mano y mirando lo que devuelve.

**El JSON** tiene siempre la misma forma:

| Caso | Cuerpo | HTTP |
| --- | --- | --- |
| Salió bien | `{ok: true, datos: {...}, mensaje?, redireccion?}` | 200 |
| Error del usuario | `{ok: false, mensaje}` | 401, 403, 404, 422 |
| Requisitos salteables | `{ok: false, confirmar: true, mensaje, avisos: [{tipo, mensaje}]}` | 409 |
| Error interno | `{ok: false, mensaje: "Ocurrió un error inesperado..."}` | 500 |

La tabla excepción → código está en un solo lugar, `Respuesta::desdeExcepcion()`. Solo el mensaje de una `ServicioException` llega al usuario; cualquier otra (una `PDOException`, un `TypeError`) va al `error_log` con archivo y línea, y el usuario ve el mensaje genérico. El token CSRF inválido devuelve **403**: se probó 419 (el código que usan algunos frameworks), pero Apache cambia los códigos que no conoce por 500.

**`Controlador`** es la base de todos. Trae el `UsuarioService` (para sacar el id del usuario logueado) y tres lectores de datos del pedido: `texto()`, `entero()` y `booleano()`. Hacen falta porque con `strict_types` un campo que llega como array (`nombre[]=x`) haría saltar un `TypeError` en el Service; acá se convierte en texto vacío o en "Faltan datos".

**`Flash`** (en `src/Helper/`) guarda mensajes de una sola vez para mostrar después de una redirección ("Cuenta creada..."). Es un Helper como `Csrf`, así que la regla de que `UsuarioService` es el único Service que toca `$_SESSION` sigue en pie.

**`volver` seguro.** El login acepta `?volver=` para regresar a la página pedida, pero solo si es una ruta `views/pages/*.php` (`Controlador::rutaInterna`). Un link armado con `volver=https://otro-sitio` o con `../` termina en el inicio.

**`UsuarioController`.**

| Método | Tipo | Qué hace |
| --- | --- | --- |
| `paginaLogin` | página | Con sesión, va directo al destino |
| `paginaRegistro` | página | Con sesión, va al inicio |
| `paginaMiCuenta` | página | Nombre y email (solo lectura) |
| `iniciarSesion` | `usuario.iniciarSesion` | Redirige a `volver` o al inicio |
| `registrar` | `usuario.registrar` | Pide la contraseña dos veces; redirige a `solicitud_alta.php` con un Flash |
| `cerrarSesion` | `usuario.cerrarSesion` | POST con CSRF (un link GET lo podría disparar otra página) |
| `cambiarContrasenia` | `usuario.cambiarContrasenia` | Actual + nueva dos veces |

Las páginas `login.php`, `registro.php`, `mi_cuenta.php`, `inicio.php` y `solicitud_alta.php` se hacen en el paso de Views; las redirecciones ya apuntan ahí.

**Pruebas.** 37 casos por CLI, en una transacción que después se deshizo: acciones inexistentes, método equivocado, CSRF faltante, falso y de una sesión anterior, campos que llegan como array, registro, login, logout, cambio de contraseña, seis formas de `volver` malicioso, el 500 sin detalles y el 409 con avisos. Además se probó `api.php` por HTTP en el Apache de XAMPP (422 del login, 403 sin token).

## Datos de ejemplo para desarrollo

`database/datos_ejemplo.sql` carga datos inventados para probar la aplicación sin pasar por el alta. **Es solo para desarrollo:** al importarlo borra todos los datos dinámicos y de progresión (grupos, secciones, protagonistas, familiares, documentos, usuarios, accesos, solicitudes, eventos) y reinicia sus AUTO_INCREMENT. No toca las tablas estáticas. Se puede reimportar cuantas veces haga falta para volver al punto de partida.

**Cómo usarlo.** En phpMyAdmin, importar en este orden:

1. `database/educarama.sql`
2. `database/seeds/catalogo_competencias.sql`
3. `database/datos_ejemplo.sql`

Para volver a empezar después de probar, alcanza con reimportar el tercero.

**Usuarios de prueba** (contraseña de todos: `educarama123`):

| Email | Caso |
| --- | --- |
| `jefe.grupo1@ejemplo.test` | Jefe del grupo 1: Jefe de todas sus secciones por derivación, sin filas en `acceso_seccion` |
| `responsable.manada@ejemplo.test` | Responsable de la Manada Luna Llena |
| `ayudante.unidad@ejemplo.test` | Ayudante de la Unidad Aconcagua (solo lectura) |
| `supervisor.grupo1@ejemplo.test` | Supervisor del grupo 1: rol de grupo, sin acceso a ninguna sección |
| `supervisor.caminantes@ejemplo.test` | Supervisor del grupo 1 y Responsable de la Comunidad Caminante Horizonte |
| `jefe.grupo2@ejemplo.test` | Jefe del grupo 2 (no ve nada del grupo 1) |
| `admin@ejemplo.test` | Admin (`es_admin = 1`), sin accesos |
| `nuevo@ejemplo.test` | Recién registrado, sin accesos, con una solicitud rechazada (con motivo) y otra pendiente |

**Qué hay.**

- **Grupo 1** "Los Aromos" (código 901): Manada, Unidad, Caminantes y Rovers, con seisenas, patrullas y equipos (Rovers sin pequeño equipo).
- **Grupo 2** "Cerro Azul" (902): Manada y Unidad.
- **Protagonistas:** 10 por sección. Algunos están sin patrulla. Hay hermanos que comparten familiares, y un Rover que además está cargado como familiar de su hermano de la Manada. Hay contactos de emergencia, y algunos chicos no tienen ninguno.
- **Eventos:**
  - del grupo entero y de algunas secciones;
  - públicos y privados;
  - uno pasado, uno en curso y uno desactivado.

**Casos de progresión.** La cabecera del archivo lista cada protagonista con su caso. En todas las ramas hay:

- recién ingresados, sin progresión;
- etapas intermedias;
- alguno en la última etapa;
- alguno con la última etapa lista para confirmar.

Para probar los avisos hay:

- uno que llegó justo al cupo de su etapa en curso;
- uno que se pasó del cupo;
- uno con el cupo completo pero con una tarea pendiente;
- uno con una competencia en proceso que ya tiene todas sus acciones y no recibió la insignia de su etapa en curso.

En la Unidad hay además uno que logró todas las etapas. Los Caminantes tienen su `elemento` en cada etapa lograda, sin repetir, y los Rovers no tienen acciones. LyL y Scouts tienen especialidades.

**Coherencia con las reglas.**

- Las etapas logradas van siempre en orden y con fechas crecientes, cada una con su cupo desarrollado y todas sus tareas.
- Cada competencia tiene el `id_etapa` de la etapa en que se eligió.
- Una desarrollada tiene todas sus acciones requeridas (todas, en Scouts y Caminantes). Una en proceso tiene solo algunas, salvo el caso a propósito.
- Las insignias se entregan con fecha al empezar cada etapa.
- La edad crece con las etapas logradas.

**Sin ids del catálogo para lo que no los tiene fijos.** Las competencias tienen id fijo en el catálogo. Las acciones no: el catálogo las borra y las vuelve a insertar sin reiniciar el AUTO_INCREMENT, así que sus ids cambian en cada importación. Por eso el archivo carga acciones, tareas, insignias y especialidades en tablas temporales con su clave natural (competencia y número, etapa y número...) y las inserta con un JOIN.

**Documentos:** no hay. El archivo vive en `storage/` y no en la base. Si había documentos subidos, al reimportar sus archivos quedan huérfanos y se pueden borrar a mano.

**Verificado:**

- Importa sin errores sobre una base recién creada con `educarama.sql` y el catálogo, y se puede reimportar sobre sí mismo.
- 24 consultas de reglas dan 0 violaciones: etapas en orden, cupos, tareas, acciones, ramas, patrullas, fechas, insignias, accesos.
- Se pasó por los Services reales, en 42 casos:
  - login de los 8 usuarios;
  - el resumen de cada caso de aviso;
  - lo que ve cada rol en protagonistas, eventos, solicitudes y accesos.

**Mantenerlo al día:** si cambia el esquema, hay que actualizar este archivo y volver a verificar que importa (está anotado en CLAUDE.md).

## ProgresionController

El Controller de la progresión: la pantalla de competencias y todas las operaciones de guardado inmediato.

**Lectura de la pantalla: `ProgresionService::pantallaCompetencias()`.** Faltaba un método que trajera todo lo que muestra la pantalla de competencias. `resumen()` solo tenía los contadores. Ahora hay uno nuevo, Ayudante en adelante, que devuelve la clase vista `PantallaCompetencias`:

- el `ResumenProgresion` de siempre;
- una `AreaEnPantalla` por pestaña, con su contador y **todas** las competencias del área (también las pendientes, que no tienen fila). Cada `CompetenciaEnPantalla` trae su estado (`null` = pendiente), el `id_etapa` en que se eligió y sus acciones como `AccionEnPantalla` (la acción y "cumplida sí/no");
- los términos de la rama (`nomenclatura`): "Senderos", "Competencias intermedias", "Seisena";
- `puedeEditar` (Responsable) y `puedeQuitar` (Jefe), para que la vista sepa qué botones mostrar.

Para no hacer una consulta por competencia, las acciones de la rama y las cumplidas del protagonista se traen en dos consultas y se agrupan en PHP. `resumen()` se partió en dos: el permiso por un lado y `armarResumen()` por el otro, así la pantalla no controla el permiso dos veces.

**404 en vez de 403.** Si el usuario no tiene ningún acceso a la sección del protagonista, la progresión responde 404, como los documentos y los eventos: un Jefe de otro grupo no puede averiguar que el protagonista existe. Si tiene acceso pero no el rol (un Ayudante que quiere avanzar una competencia), sigue siendo 403. Lo resuelve `cargarConNivel()`, que además devuelve el rol para armar `puedeEditar` y `puedeQuitar`.

**Fechas no futuras.** Confirmar una etapa y otorgar una insignia aceptan una fecha, porque la ceremonia o la entrega física pueden ser de otro día. Es opcional (vacía = hoy) y puede ser pasada, pero **no futura**: "La fecha no puede ser posterior a hoy.". Las demás operaciones no reciben fecha desde el navegador: usan la de hoy.

**Insignias de la rama.** `otorgarInsignia` no controlaba que la insignia fuera de la rama del protagonista: se le podía dar a un Scout la de Pata Tierna. Ahora la rechaza ("La insignia Pistas no es de Lobatos y Lobeznas."). Las insignias sin rama (`id_rama` NULL) siguen valiendo para cualquiera.

**El Controller.** `paginaCompetencias` arma la página con la ficha del protagonista (`ProtagonistaService::buscarFicha`) y la pantalla. Las 12 acciones (`progresion.*` en `api.php`) son competencias, acciones y tareas, etapas, insignias y especialidades, cada una con su par avanzar/retroceder, marcar/desmarcar o confirmar/quitar. Todas pasan por un mismo esqueleto, `operar()`: saca el usuario y el protagonista, hace la operación y responde

```json
{"ok": true, "datos": {"sugerencias": [...], "resumen": {...}}}
```

El resumen viene actualizado después de cada clic, así el JS repinta "Etapa en curso: X/cupo", el total, el contador de cada pestaña, lo adeudado y el aviso de insignia sin calcular nada por su cuenta. Se arma como un array chico de números, ids y textos, no como los Models enteros. Si faltan requisitos salteables, Api responde 409 con los avisos y el JS repite el mismo pedido con `confirmarIgual=1`.

**Pruebas.** 56 casos por CLI, sobre una base temporal armada con `datos_ejemplo.sql` y dentro de una transacción que después se deshizo:

- la pantalla según cada rol (Jefe, Responsable, Ayudante), Rovers sin acciones;
- el 404 al Supervisor de grupo, al Responsable de otra sección y al Jefe de otro grupo;
- el 409 por cupo y su `confirmarIgual`;
- la sugerencia al cumplir la última acción requerida;
- tareas, etapas con fechas futuras, mal escritas y pasadas, y el elemento de Caminantes;
- insignias de otra rama, y quitar etapas e insignias (solo Jefe);
- especialidades.

Las 49 pruebas de `ProgresionService` siguen pasando sin cambios.

## ProtagonistaController, PatrullaController y SeccionService

**`SeccionService`, nuevo.** Hasta ahora las secciones se leían solo por dentro de otros Services, y ninguna página podía pedir "los datos de esta sección". Ahora la sección tiene su propio Service, que va a ser la base del futuro perfil de sección. Por ahora hace dos cosas:

- `encabezado($idUsuario, $idSeccion)` devuelve la clase vista `SeccionEnPantalla`: la sección, su rama (para la clase CSS y los nombres), el grupo, los términos de `nomenclatura` y el rol del usuario. Trae además `puedeVerDetalle()` (Ayudante), `puedeEditar()` (Responsable), `puedeEliminar()` (Jefe) y `pequenioEquipo()`, que da "Seisena", "Patrulla", "Equipo" o null en Rovers. Basta cualquier acceso a la sección, incluso Supervisor; sin acceso, 404.
- `listarPorGrupo($idUsuario, $idGrupo)`: con cualquier rol de grupo, todas las secciones (los roles de grupo valen para "el listado de secciones"); sin rol de grupo, solo aquellas a las que tiene acceso. Cada una trae el rol del usuario, que es null si la ve solo por su rol de grupo.

Las secciones no se crean ni se renombran acá: hoy solo nacen al aprobar una solicitud de alta.

**404 en Protagonista y Patrulla.** Igual que en Progresión: sin ningún acceso a la sección, 404 ("El protagonista no existe.", "La patrulla no existe.", "La sección no existe."); con acceso pero sin el rol, 403. Para no repetir el mismo `if` en cada Service, `PermisoService` suma `exigirVisibleEnSeccion($idUsuario, $idSeccion, $minimo, $mensaje404)`. En el pase de sección, sin acceso al destino, la respuesta es "La sección de destino no existe.". Familiares todavía responde 403: se iguala cuando hagamos su Controller.

**Lo nuevo en los Services.**

- `ProtagonistaService::seccionesDestino()`: las otras secciones del grupo donde el usuario es Responsable, para el select de "Cambiar de sección" del perfil. Vacía si no puede hacer ningún pase.
- **Rovers sin patrullas:** `PatrullaService::crear` rechaza crear una patrulla en una rama sin pequeño equipo ("Esta rama no tiene pequeños equipos."). Lo decide `nomenclatura`, que no tiene `pequenio_equipo` para Rovers, y no el id de la rama: es el mismo dato que usa la vista para mostrar u ocultar el término.
- `PatrullaService::quitarIntegrante($idUsuario, $idPatrulla, $idProtagonista)`: saca al protagonista de **esa** patrulla. Si no es integrante, se rechaza. Así, un pedido viejo (otra pestaña abierta) no lo saca de la patrulla a la que se pasó mientras tanto.

**`ProtagonistaController`.**

| Método | Tipo | Qué hace |
| --- | --- | --- |
| `paginaListado` | `protagonistas.php?id_seccion=` | Encabezado, protagonistas y patrullas (Ayudante) |
| `paginaPerfil` | `perfil_protagonista.php?id=` | Ficha, encabezado, resumen de progresión, familiares, documentos y secciones de destino. Todo de lectura |
| `paginaFormulario` | `protagonista_form.php` | `?id_seccion=` es alta, `?id=` es edición. Trae las patrullas para el select. Responsable |
| `guardar` | `protagonista.guardar` | Alta si no viene `id`, edición si viene. Redirige al perfil con un Flash |
| `cambiarSeccion` | `protagonista.cambiarSeccion` | "Pasó a Unidad Aconcagua. Quedó sin patrulla." (en Rovers, sin la segunda parte) |
| `eliminar` | `protagonista.eliminar` | Jefe. Redirige al listado de la sección |

Al editar, la sección sale de la base y no del formulario: un `id_seccion` adulterado se ignora. El DNI se acepta con puntos y espacios ("12.345.678"), como lo escribe la gente. Un año de ingreso con letras da "El año de ingreso no es válido." y no el genérico "Faltan datos".

**`PatrullaController`.**

| Método | Tipo | Qué hace |
| --- | --- | --- |
| `paginaListado` | `patrullas.php?id_seccion=` | Patrullas con sus integrantes y la lista de los que no tienen, armadas con una sola consulta |
| `paginaPerfil` | `perfil_patrulla.php?id=` | Integrantes y candidatos (el resto de la sección, para "Agregar") |
| `crear` / `renombrar` / `eliminar` | `patrulla.*` | Crear redirige al perfil de la nueva; eliminar (Jefe), al listado |
| `asignarIntegrante` / `quitarIntegrante` | `patrulla.*` | Si estaba en otra patrulla, asignar lo pasa a esta |

Los mensajes evitan los adjetivos con género ("Se creó «Equipo Norte».") porque Seisena y Patrulla son femeninos y Equipo es masculino.

**Pruebas.**

- **62 casos nuevos por CLI**, sobre los datos de ejemplo, en una transacción que se deshizo:
  - lo que ve cada rol en `SeccionService`;
  - las páginas con sus 403 y 404;
  - el alta con DNI con puntos, repetido o con letras, y los errores del año y de la fecha;
  - la edición que ignora la sección del formulario;
  - el pase con sus mensajes y la baja;
  - todo el ciclo de las patrullas, incluido el rechazo en Rovers.
- **Regresión:** se repitieron las pruebas de todos los Services. La única diferencia es la esperada: el pase a una sección sin acceso ahora es 404. Protagonistas 41, familiares 27, progresión 49, eventos 60, accesos 35, usuarios 46 y documentos 25, más las de los Controllers de Progresión (56) y Usuario (37).

## DocumentoController

Los documentos se ven y se suben desde el perfil del protagonista: no tienen página propia.

**La descarga tiene su propia puerta: `public/descargar.php?id=`.** Es un GET que responde un archivo, no un JSON, y se abre desde un link o un `<img src>`, así que no pasa por `api.php`. Llama a `Pagina::cargar()` igual que una página: sin sesión, va al login; sin acceso a la sección, 404 en la página de error, y no se confirma que el documento existe. `DocumentoController::descargar` pide el documento al Service, que controla toda la pertenencia, y devuelve `Respuesta::archivo`. Recién entonces el almacén lo envía.

- `&ver=1` muestra una imagen en el navegador (`inline`). Un PDF baja siempre como adjunto, aunque venga `ver=1`: lo decide el almacén mirando el tipo real del archivo.
- No lleva CSRF: es solo lectura.

**Archivo que falta en el disco → 404, no 500.** Si la fila existe pero el archivo no está (alguien lo borró a mano), el almacén lanza `ArchivoInvalidoException`, que no es una `ServicioException`. `Respuesta::emitir()` la traduce a 404 "El archivo no está disponible.". `Pagina::cargar()` ahora también atrapa los errores al **emitir** una respuesta, no solo al armarla. Si ya salió parte del archivo, no intenta mostrar otra página.

**Archivo más grande que `post_max_size`: 413.** Cuando un pedido supera `post_max_size`, PHP lo descarta entero y `$_POST` y `$_FILES` llegan vacíos, sin ningún aviso. Sin el token, `Api` respondía "La sesión expiró", que confunde. Ahora, ante un POST vacío, `Api` pregunta primero al almacén (`verificarLimiteDelServidor`) y, si fue por tamaño, responde 413 con el mismo mensaje de "supera los 5 MB". El 413 es un código estándar, así que Apache no lo cambia.

**Acciones** (`api.php`):

| Acción | Quién | Qué hace |
| --- | --- | --- |
| `documento.subir` | Responsable | `id_protagonista`, `archivo` (FormData), `tipo_documento`, `descripcion`. Redirige al perfil con "Documento subido." |
| `documento.actualizar` | Responsable | Tipo y descripción; responde "Cambios guardados." y el JS actualiza la fila |
| `documento.eliminar` | Jefe | Redirige al perfil con "Documento eliminado." |

El tipo de documento sigue siendo texto libre, sin sugerencias.

**Pruebas.** La subida no se puede probar por CLI: `move_uploaded_file` solo acepta archivos subidos de verdad por HTTP. Por eso se probó con un servidor `php -S` que corre los `api.php` y `descargar.php` reales sobre una base temporal con los datos de ejemplo, y `curl` subiendo archivos de verdad. Fueron 29 casos:

- subir un PDF y una PNG;
- sin archivo, tipo real no permitido, más de 5 MB y más que `post_max_size` (413);
- sin token, otra sección (404) y Ayudante (403);
- descargar como adjunto y en línea, con los headers `nosniff` y `no-store`;
- Jefe de grupo por derivación;
- sin acceso (404 en HTML, sin el archivo), sin sesión (al login) y archivo faltante en el disco (404);
- editar, eliminar (Jefe) y eliminar dos veces.

Además se repitieron las pruebas de los otros Controllers (Usuario 37, Progresión 56, Protagonista 62) y las 25 de `DocumentoService`.

## FamiliarController

Los familiares se gestionan siempre desde un protagonista. La lista ya se ve en su perfil; este Controller suma el formulario y las acciones.

**Lo que cambió en `FamiliarService`.**

- **404 sin acceso:** igual que Protagonista y Patrulla. Sin ningún acceso a la sección del protagonista responde "El protagonista no existe." (404); con acceso pero sin el rol, 403. Con esto, todos los Services de sección responden igual.
- **`vinculadoAOtros()`:** dice si el familiar también está vinculado a otros protagonistas, para avisar en el formulario que los cambios de datos y contacto se ven en todos. Solo dice sí o no, nunca cuántos ni cuáles, porque pueden ser de otro grupo (la misma idea que el aviso de DNI ya cargado, que muestra solo el nombre).
- **`desvincular()` devuelve un booleano:** `true` si además se borró la ficha de familiar, porque era su último protagonista. Así el Controller sabe qué mensaje mostrar.

**`FamiliarController`.**

| Método | Tipo | Qué hace |
| --- | --- | --- |
| `paginaFormulario` | `familiar_form.php?id_protagonista=` (alta) o `&id_familiar=` (edición) | Ficha del protagonista, encabezado de la sección, ficha del familiar y `compartido`. Responsable |
| `guardar` | `familiar.guardar` | Alta si no viene `id_familiar`, edición si viene. Vuelve al perfil: "Familiar agregado." / "Cambios guardados." |
| `desvincular` | `familiar.desvincular` | Vuelve al perfil: "Se desvinculó a Ana Pérez." o "...y se borró su ficha de familiar." |

Los dos avisos salteables viajan como siempre, en un 409 que el JS confirma y repite con `confirmarIgual=1`:

- en el alta, un DNI ya cargado: "Ana Pérez ya está cargada con ese DNI. ¿Vincularla...?";
- al desvincular, un familiar que queda sin protagonistas.

**`dni()` y `fechaOpcional()` pasaron a `Controlador`.** Los usaban `ProtagonistaController` y `ProgresionController`, y ahora también `FamiliarController`.

**Pruebas.** 31 casos por CLI, sobre los datos de ejemplo, en una transacción que se deshizo:

- el formulario según cada rol, con 404 y 403;
- `compartido` con los hermanos Ledesma;
- alta con DNI con puntos, email y teléfono inválidos, y el DNI del propio protagonista;
- DNI ya cargado: el 409 trae solo el nombre y no pisa los datos guardados;
- un Rover que pasa a ser familiar;
- la edición;
- desvincular un familiar compartido (sin preguntar) y el último protagonista (409 y borrado de la ficha; la persona queda).

Se repitió toda la batería: los Controllers de Usuario (37), Progresión (56), Protagonista (62) y Documento (29), y los Services. En familiares, la única diferencia es la esperada: un Responsable de otra sección ahora recibe 404.

## EventoController, SeccionController e InicioController

Con estos tres Controllers quedan cubiertas las pantallas que faltaban para navegar: la de inicio (adonde ya mandaba el login), la del grupo, el perfil de sección y los eventos.

**`EventoService`: pantallas y opciones del formulario.**

- `pantallaGrupo()` y `pantallaSeccion()` devuelven `EventosEnPantalla`: el listado de siempre más los nombres de las secciones del grupo, si el usuario puede crear y ver inactivos, y, por cada evento (`EventoEnPantalla`), si puede editarlo (y desactivarlo o reactivarlo) y si puede eliminarlo. Así la vista no repite reglas de permisos.
- `opcionesFormulario()` devuelve `OpcionesEvento`: las secciones del grupo donde el usuario es Responsable o más, y si puede elegir "todo el grupo" (Responsable de grupo). Si no tiene ninguna opción, 403. Así el formulario no ofrece algo que después el Service rechazaría.
- `paraEditar()` controla, antes de mostrar el formulario de edición, que el usuario pueda editar el evento tal como está.

**404 también en eventos.** Un grupo o una sección a los que el usuario no tiene ningún acceso responden 404. La pantalla de eventos del grupo sigue pidiendo un rol de grupo: quien entra solo por una sección recibe 403, con un mensaje que lo manda a la pantalla de su sección. En las pruebas viejas cambiaron dos casos, los dos de 403 a 404: el Jefe de otro grupo que intenta crear un evento y el Responsable de grupo que pide los eventos de una sección (los roles de grupo no dan acceso al detalle de una sección).

**Próximos eventos para quien entra solo por una sección.** `proximosPorGrupo()` pedía un rol de grupo. Ahora, a quien entra solo por secciones le junta los próximos de esas secciones, sin repetir y por fecha. Así el inicio y la pantalla del grupo no le quedan vacíos.

**`SeccionService` suma:**

- `grupo()`: el grupo, el rol de grupo del usuario (null si entra solo por secciones) y las secciones que ve (`GrupoEnPantalla`);
- `misGrupos()`: los grupos donde tiene un rol de grupo o alguna sección, por nombre. Vacío para el recién registrado y para el admin;
- `conteos()`: cantidad de protagonistas, cantidad por patrulla y cuántos no tienen patrulla (`ConteoSeccion`). Como no tiene datos personales, alcanza con cualquier acceso a la sección, incluso Supervisor.

**`EventoController`.**

| Método | Tipo | Qué hace |
| --- | --- | --- |
| `paginaListado` | `eventos.php?id_grupo=` o `?id_seccion=` (`&inactivos=1`) | Modo grupo o modo sección, con la pantalla de eventos |
| `paginaFormulario` | `evento_form.php?id_grupo=` (alta) o `?id=` (edición) | Opciones y, si es edición, el evento |
| `guardar` | `evento.guardar` | Alta o edición; el grupo de una edición sale de la base |
| `desactivar` / `reactivar` / `eliminar` | `evento.*` | Recargan el listado de donde se vino |

Todas las acciones vuelven con un Flash al listado que mandó la página (`volver`, validado con `rutaInterna`; si no sirve, el listado del grupo). El formulario manda `alcance` (`grupo` o `secciones`) explícito: si no, destildar todas las casillas convertiría el evento en uno de todo el grupo sin querer. Las casillas llegan como `secciones[]` y las lee el nuevo `Controlador::listaDeEnteros()`.

**`SeccionController`.** Reemplaza a `perfil_rama.php` y `ramas.php` del legacy, donde "rama" era la instancia.

- `paginaPerfil` (`perfil_seccion.php?id=`): encabezado, números y próximos eventos. La ve cualquiera con acceso a la sección, incluso un Supervisor.
- `paginaGrupo` (`grupo.php?id=`): las secciones que ve el usuario y los próximos eventos.

**`InicioController`** (`inicio.php`): mis grupos, cada uno con sus próximos eventos. Si el usuario no tiene ningún grupo, muestra su solicitud de alta más reciente (pendiente, o rechazada con su motivo), o nada si nunca envió una: la vista ofrece el link para pedir el alta. Trae también el `UsuarioSesion`, para mostrarle al admin el link a las solicitudes pendientes.

**Pruebas.** 54 casos por CLI, sobre los datos de ejemplo, en una transacción que se deshizo:

- qué eventos y qué botones ve cada rol (Jefe, Responsable de sección, Supervisor de grupo, Supervisor y Responsable a la vez, Jefe de otro grupo, admin);
- las opciones del formulario;
- guardar con alcance faltante, sin secciones, con secciones ajenas o con basura, y con prioridad inválida o fecha pasada;
- el `volver` malicioso;
- desactivar, reactivar y eliminar;
- el perfil de sección con sus números (Rovers sin patrullas);
- la pantalla del grupo;
- el inicio de cada tipo de usuario.

Además se repitió toda la batería: los Controllers de Usuario (37), Progresión (56), Protagonista (62), Familiar (31) y Documento (29), y los Services.

## AccesoController y SolicitudAltaController

Son los dos últimos Controllers: con ellos queda cubierta toda la funcionalidad de los Services.

**404 en accesos.** Igual que en el resto: sin ningún acceso al grupo o a la sección, 404 (antes, un Jefe de otro grupo recibía 403 y se enteraba de que existían); con acceso pero sin ser Jefe, 403. Para decidirlo se sumó `PermisoService::tieneAccesoAlGrupo()` (un rol de grupo o un rol en alguna de sus secciones), que también usa ahora `EventoService`. Cambiaron dos casos de las pruebas viejas, de 403 a 404: el Jefe de otro grupo que pide la lista o que agrega un acceso.

**Gestión de accesos.**

- **La pantalla ya viene con los botones.** `AccesoService::pantallaGrupo()` y `pantallaSeccion()` devuelven, por cada fila, los roles que se le pueden asignar y si se puede quitar (`AccesoEnPantalla`), y arriba los roles con que se puede agregar. Así la vista no repite las reglas ni ofrece botones que el Service rechazaría:
  - Al **único Jefe del grupo** no se le ofrece cambiar el rol ni quitarlo.
  - El **Jefe de grupo** puede todo en la sección, con cualquier rol.
  - El **Jefe de sección** solo cambia el rol hasta Responsable de quien no es Jefe. No agrega, no quita y no toca a otro Jefe ni a sí mismo.
- **`accesos.php`** tiene dos modos como la de eventos: `?id_grupo=` y `?id_seccion=`.
- **Acciones:** `acceso.dar` (email y rol), `acceso.cambiarRol` y `acceso.quitar`. Llevan el `ambito` explícito (`grupo` o `seccion`) con su id, y vuelven a la lista con un Flash.
- **Casos de borde:**
  - Si el Jefe de grupo se **baja el rol** a sí mismo (había otro Jefe), ya no puede ver la lista de accesos: vuelve a la pantalla del grupo.
  - Si se **quita** a sí mismo, vuelve al inicio.

**Alta de un grupo.**

- **Para el solicitante:** `SolicitudAltaService` suma `ramas()` (para el select de cada sección), `misSolicitudesEnPantalla()` y `detalle()`. Todas devuelven `SolicitudEnPantalla`: la solicitud con el nombre oficial de la rama de cada sección ("Lobatos y Lobeznas") y, solo para un admin, el nombre y el email de quien la envió. El hash de contraseña del solicitante no llega a la vista.
- **Para el admin:** `pendientesEnPantalla()`.
- **`solicitud_alta.php`** (cualquier usuario con sesión) tiene el formulario y debajo mis solicitudes con su estado y, si se rechazó, el motivo. Con una solicitud pendiente se oculta el formulario. Las secciones son filas con nombre y rama; llegan como `seccion_nombre[]` y `seccion_rama[]`. Una fila completamente vacía se descarta; una a medias la rechaza el Service con su mensaje.
- **`solicitudes.php`** (solo admin, 403 al resto) lista las pendientes de la más vieja a la más nueva.
  - **Aprobar:** deja el Flash "Grupo 910 «Grupo Scout Nuevo» creado. Pablo Ortiz quedó como Jefe del grupo.".
  - **Rechazar:** pide el motivo, que ve el solicitante.
- **Acciones:** `solicitud_alta.enviar` (vuelve al inicio), `solicitud_alta.aprobar` y `solicitud_alta.rechazar` (vuelven a la revisión).

**Pruebas.** 66 casos por CLI sobre los datos de ejemplo, en una transacción que se deshizo:

- *Accesos:* la lista y los botones de cada rol (Jefe de grupo, Responsable, Supervisor, Jefe de otro grupo, admin); agregar, cambiar y quitar; email sin cuenta, repetido o con rol inválido; el último Jefe; el Jefe que se baja o se quita a sí mismo; el Jefe de sección con todos sus límites.
- *Solicitudes:* mis solicitudes con el motivo; enviar (código usado, sin secciones, secciones a medias, repetidas, listas desparejas o anidadas); el 403 a cualquier no admin; rechazar con y sin motivo; aprobar (se crean el grupo, las secciones y el Jefe) y aprobar dos veces; el inicio del nuevo Jefe.

Se repitió toda la batería de Controllers y Services.

## Views: layout base, estilos, JavaScript y menú lateral

Arranca el paso 5. Antes de la primera pantalla se armó lo que todas comparten. Las reglas cortas quedaron en CLAUDE.md, en "Views".

**Cómo se arma una página.** Cada una de `views/pages/` carga sus datos con `Pagina::cargar()` y después se envuelve con dos includes:

- `views/includes/cabecera.php` abre `<html>`, el `<head>`, la barra superior, el menú lateral y el contenedor, y muestra los mensajes Flash que dejó la acción anterior;
- `views/includes/pie.php` cierra el contenedor, pone el pie y los scripts.

La página define antes `$tituloPagina` y, si hace falta, `$claseRama` (colores de la rama), `$sinMenu` (login, registro, error), `$contenedorAngosto` y `$scriptsPagina`. La cabecera pide los datos del menú **antes de imprimir nada**, así que sin sesión redirige al login sin haber mandado HTML.

**Menú lateral: un drawer.** Es un offcanvas de Bootstrap, un solo elemento para los dos tamaños:

| Pantalla | Comportamiento |
| --- | --- |
| Desde 992 px | Está a la vista y empuja el contenido. El botón de la barra superior lo oculta o lo muestra, y recuerda la elección en `localStorage`. Un script en el `<head>` aplica lo recordado antes de dibujar, para que no parpadee |
| Menos de 992 px | Cerrado. Se abre encima del contenido con fondo oscuro y se cierra con la X, con el fondo o con Esc. Si se agranda la ventana con el cajón abierto, se cierra |

Las animaciones se activan después del primer dibujo: si no, el menú "se deslizaría" en cada página que se abre.

**El menú es contextual.** Ya no hay un solo grupo implícito como en el legacy. `MenuController` pide `SeccionService::misGrupos()` y la vista arma, por cada grupo, "Resumen del grupo", "Eventos del grupo" (con rol de grupo) y "Accesos del grupo" (Jefe de grupo); y por cada sección a la que entra, un bloque desplegable (se abre solo el de la página actual) con Perfil, Protagonistas, Seisenas, Patrullas o Equipos (según la rama; en Rovers no hay), Eventos y Accesos (Jefe), según su rol. **Un link que el usuario no puede usar no se muestra.** Una sección que ve solo por su rol de grupo no tiene links propios. Al final: Mi cuenta, Solicitar el alta de un grupo, Solicitudes de alta (admin) y Cerrar sesión.

**`public/js/educarama.js`.** Hace tres cosas:

- **Habla con `api.php`:**
  - `llamar()` manda el POST con el token CSRF.
  - `ejecutar()` agrega el flujo completo: si la respuesta es 409 muestra los avisos y, si el usuario acepta, repite con `confirmarIgual=1`; si trae `redireccion`, va ahí; sin sesión (401), va al login; cualquier otro error se muestra en un aviso.
- **Conecta formularios y botones declarados con atributos**, así la mayoría de las páginas no necesita JavaScript propio:
  - `<form data-accion="usuario.iniciarSesion">`;
  - un botón con `data-accion`, `data-datos` (JSON con sus campos), `data-confirmar` y `data-peligro`;
  - también `data-texto`, `data-boton`, `data-reiniciar` y `data-recargar`.
- **Maneja el menú** y el botón de mostrar u ocultar contraseña.

Las pantallas complejas (competencias, por ejemplo) tienen su propio archivo en `public/js/`, que declaran con `$scriptsPagina`. Las confirmaciones usan SweetAlert2 (decisión del equipo; queda por CDN con versión fija). Si no carga, se usan los cuadros nativos del navegador.

**Estilos: los del legacy, corregidos.** `public/css/styles.css` sale del legacy. La paleta, la fuente Ubuntu (local) y las clases por rama se mantienen. Lo que estaba mal:

| Problema del legacy | Corrección |
| --- | --- |
| El menú usaba `--rama-primario` como color de texto: en Manada, letras **amarillas sobre blanco**. El `<style>` del include intentaba arreglarlo con variables que el CSS no usaba | El texto del menú es siempre violeta; el color de la rama queda en un circulito al lado de cada sección |
| El banner de título era violeta con texto oscuro (ilegible) | Variables `--banner-inicio`, `--banner-fin` y `--banner-texto` por rama: texto blanco sobre violeta y rojo, oscuro sobre amarillo, verde y celeste. **El banner de Rovers termina en rojo oscuro y no en rosa**, para que el texto blanco se lea hasta el final |
| Menú móvil: una tira de íconos de 68 px | Ahora es el drawer |
| Links `/Educarama/...` escritos a mano | `url()` |
| Ubuntu cargada dos veces (Google y local) | Solo la local |
| Un `<h1>` en la barra superior de cada página, más el de la página | La barra superior no es un `<h1>` |
| El pie no era fijo pero el contenido reservaba espacio como si lo fuera | El pie queda al final del contenido y se corre con el menú |
| Un número de versión fijo en el CSS para que el navegador lo recargue | La versión es la fecha del archivo |

Al portarlo apareció un detalle de Bootstrap 5.3.3: su regla es `.offcanvas.offcanvas-start { top: 0 }`, más específica que una clase sola, por eso el menú arrancaba pegado arriba, debajo de la barra superior. Se resolvió subiendo la especificidad de la regla propia.

**Otras piezas.**

- `e()` (en `config/bootstrap.php`, junto a `url()`): escapa HTML. Todo dato que viene de la base o del usuario se imprime con ella.
- `Helper\Vista`: `claseRama()` (por **id** de rama, nunca por el nombre de la sección, que era un bug del legacy), `iconoRama()`, `colorPrioridad()`, `colorEstado()`, `periodo()` y `fecha()`.
- `MenuController`: los datos del menú.
- `public/index.php`: manda a `inicio.php` (que, sin sesión, redirige al login con `volver`).
- La página de error (`views/includes/error.php`) usa este layout, **sin menú** a propósito: si el error fuera justamente al cargar el menú, pedirlo de nuevo volvería a fallar en un círculo.

## Views: login, registro, inicio, Mi cuenta y solicitud de alta

Las pantallas que hacen falta para entrar a la aplicación y probar lo demás:

| Página | Qué hace |
| --- | --- |
| `login.php` | Iniciar sesión. Con sesión abierta redirige al destino. `?volver=` solo acepta páginas del proyecto |
| `registro.php` | Crear la cuenta (repite la contraseña). Después lleva a la solicitud de alta |
| `inicio.php` | Con accesos: cada grupo con sus secciones (tarjetas con el color de la rama; las que se ven solo por el rol de grupo, atenuadas y sin link) y los próximos eventos. Sin accesos: el estado de la solicitud de alta (pendiente, o rechazada con su motivo) y cómo seguir |
| `mi_cuenta.php` | Nombre y correo (solo lectura) y cambio de contraseña |
| `solicitud_alta.php` | El formulario (grupo y secciones como filas que se agregan y quitan, con `public/js/solicitud_alta.js`) y mis solicitudes con su estado y motivo. Con una pendiente, sin formulario |

**Pruebas.** Se probaron de tres maneras, sobre los datos de ejemplo:

- **HTTP (40 casos):**
  - Las páginas generadas para cada rol: el menú de un Jefe de grupo, de un Responsable de sección, de un Supervisor, del admin y de un recién registrado.
  - Que un link no permitido no aparezca.
  - Que un nombre con HTML se escape en el inicio y en el menú.
  - Que el hash de la contraseña nunca se imprima.
  - Las redirecciones, los archivos estáticos y que el 404 use el layout.
- **En un navegador real (Edge sin ventana, 9 casos):**
  - Login con contraseña incorrecta y correcta.
  - El menú: abrir, ocultar, recordar y volver a mostrar en escritorio, y abrir y cerrar con la X en móvil.
  - El cierre de sesión con su confirmación.
  - Las filas de la solicitud de alta.
  - El flujo del 409 (el aviso de DNI ya cargado, al cancelar y al aceptar).
- **Capturas** de escritorio y móvil para revisar el aspecto y el contraste. La ventana mínima de Edge sin interfaz es de 500 px, así que las capturas "de móvil" se tomaron a 500 px de ancho.

## Views: perfil del protagonista, competencias y etapas e insignias

Las dos pantallas prioritarias de CLAUDE.md, más una tercera que hacía falta: la de etapas e insignias.

**Perfil del protagonista** (`perfil_protagonista.php`). Tiene dos pestañas:

- **Perfil:**
  - el banner con nombre, edad y sección, con los colores de la rama;
  - los datos (DNI, nacimiento, patrulla, año de ingreso, Promesa);
  - la progresión: etapa actual y en curso, la barra con el total real (en el legacy era un 60 % fijo), el aviso de insignia pendiente y el de etapa lista, y los botones a Competencias y a Etapas e insignias;
  - los familiares, con los contactos de emergencia primero, teléfono y correo clicables, y Editar y Desvincular.
- **Documentos:** ver (las imágenes, en el navegador), descargar, subir en un modal, editar el tipo y la descripción en línea, y eliminar. Todo pasa por `descargar.php`: el legacy enlazaba directo al archivo.

El ⋮ del banner ofrece Editar datos, Cambiar de sección (un modal con las secciones de destino que permite el Service) y Eliminar (con una confirmación que dice qué se borra), cada uno solo para el rol que lo puede hacer.

**La pestaña por el `#`.** `educarama.js` abre la pestaña que indica el `#` de la URL y lo actualiza al cambiar de pestaña. Sirve para cualquier página con pestañas. Subir o eliminar un documento redirige a `perfil_protagonista.php?id=N#documentos`, así el usuario vuelve a la lista que estaba mirando.

**Competencias** (`gestionar_competencias.php` + `public/js/competencias.js`). Se hizo como la describe CLAUDE.md:

- una pestaña por área, con todas las competencias de la rama;
- un botón que solo avanza ("Elegir", "Desarrollada");
- ⋮ → Corregir, que muestra "Volver atrás" en cada una;
- las acciones en un desplegable (en LyL, las opcionales marcadas; en Rovers no hay);
- el filtro por estado y los contadores arriba y en cada pestaña.

PHP arma todo el HTML inicial; el JS solo cambia lo que corresponde después de cada clic, con el resumen que devuelve el servidor. Cada competencia elegida dice en qué etapa se eligió: para eso `PantallaCompetencias` suma `nombresEtapas`. Si la operación falla, la casilla vuelve a como estaba.

Las sugerencias del servidor se ofrecen **con un botón que hace lo sugerido**:

- "Cumplió todas las acciones requeridas. ¿Dar la competencia por desarrollada?" la avanza ahí mismo.
- "¿Confirmar la etapa?" lleva a Etapas e insignias.

**Etapas e insignias** (`progresion.php`). CLAUDE.md pide que las insignias tengan "un submenú propio del protagonista", y las tareas de etapa y "Confirmar etapa" no tenían pantalla. Esta página junta las tres cosas:

- **Etapas:**
  - las logradas, con su fecha y, en Caminantes, su elemento. "Quitar" aparece solo en la última y solo para el Jefe.
  - la en curso, con sus tareas tildables, lo que le falta para confirmarla y el formulario "Confirmar etapa": fecha (hasta hoy) y, en Caminantes, el elemento. Si falta algo, `educarama.js` muestra lo adeudado y se puede confirmar igual.
  - las que vienen, de solo lectura.
- **Insignias:** las de la rama y las generales (`id_rama` NULL, que el repositorio ahora también trae con `listarParaRama()`). Las entregadas tienen su fecha y las puede quitar el Jefe; la que le corresponde por la etapa en curso aparece resaltada, con el formulario para registrar la entrega.
- **Especialidades** (solo LyL y Scouts): arriba las suyas, con su estado; abajo, el resto por área, en un acordeón, para empezar una.

Cada acción recarga la página (`data-recargar`). Los datos los arma un método nuevo, `ProgresionService::pantallaEtapas()` (Ayudante en adelante), con las clases vista `PantallaEtapas`, `EtapaEnPantalla`, `TareaEnPantalla`, `InsigniaEnPantalla` y `EspecialidadEnPantalla`. Si la rama pide elemento sale del Service (`pideElemento`), no de un id escrito en la vista.

**Insignias sin imágenes.** Como dice "Para más adelante", las rutas de imágenes no están definidas: por ahora cada insignia es un ícono con su nombre. La Promesa es un dato del protagonista y no las dos imágenes fijas del legacy.

**Pruebas.** Sobre los datos de ejemplo:

- **HTTP (34 casos):**
  - Lo que genera cada rol en las tres pantallas: los botones según Responsable, Ayudante o Jefe, y el 404 para otro grupo, para el Supervisor y para el Ayudante de otra sección.
  - Que las competencias, sus estados y las acciones coincidan con la base.
  - Rovers sin acciones ni especialidades.
  - Caminantes con el elemento.
- **En el navegador (10 casos):**
  - Elegir una competencia (estado, etapa y contador).
  - El aviso de cupo, que no guarda hasta aceptar.
  - La sugerencia al cumplir la última acción requerida.
  - El filtro y Corregir.
  - La pestaña por `#`.
  - Tildar una tarea.
  - Confirmar una etapa con una tarea pendiente.
  - Registrar una insignia.
- **Capturas** de escritorio y móvil.

Se repitió toda la batería de Controllers y Services.

## Formulario de protagonista (`protagonista_form.php`)

Sirve para el alta (`?id_seccion=`) y la edición (`?id=`) de un protagonista. Usa `ProtagonistaController::paginaFormulario`, que trae la sección (con permisos), las patrullas y, si es edición, la ficha del protagonista.

**Decisiones:**

- **DNI solo lectura en edición.** En el alta se escribe y acepta puntos/espacios; en la edición se muestra formateado y se manda como hidden. Cambiarlo es raro y riesgoso por la unicidad en `persona`.
- **Patrullas solo si la rama tiene pequeño equipo.** El select aparece cuando `SeccionEnPantalla::pequenioEquipo()` no es null (Rovers no lo tiene). La opción por defecto es "Ninguna".
- **Contenedor angosto** (`$contenedorAngosto = true`): el formulario no necesita el ancho completo.
- **Sin JS propio:** `data-accion="protagonista.guardar"` y `educarama.js` se ocupan de todo. El 409 (confirmaciones del Service) también funciona sin código extra.

**Campos:** DNI, nombre y apellido (obligatorios), fecha de nacimiento (opcional, `type="date"`), patrulla (select, opcional), año de ingreso (texto con `inputmode="numeric"`, opcional) y promesa (checkbox).

**Botones de cancelar:** en edición vuelven al perfil del protagonista; en alta, al listado de la sección.

## Formulario de familiar (`familiar_form.php`)

Sirve para agregar un familiar a un protagonista (`?id_protagonista=`) y para editarlo (`?id_protagonista=&id_familiar=`). Usa `FamiliarController::paginaFormulario`.

**Decisiones:**

- **DNI siempre editable**, incluso en edición: a diferencia del protagonista, el familiar puede estar duplicado (el aviso del 409 lo maneja `educarama.js` con `confirmarIgual`).
- **Aviso de familiar compartido:** si `$datos['compartido']` es true, un alert info avisa que los cambios de datos se ven en todos los protagonistas vinculados. No dice cuántos ni cuáles (regla de CLAUDE.md).
- **Contenedor angosto.**
- **Sin JS propio.**

**Campos:** DNI, nombre y apellido (obligatorios), fecha de nacimiento (opcional), teléfono (texto libre, opcional), email (validado, opcional), relación (texto libre, opcional, ej. "Madre") y contacto de emergencia (checkbox).

**Cancelar** vuelve al perfil del protagonista.

## Reestructuración del menú lateral (drawer)

El menú dejó de agrupar los links por grupo y sección (un bloque por cada uno) y ahora tiene items fijos por concepto: **Grupos** (el único dropdown), **Secciones**, **Protagonistas**, **Equipos**, **Eventos** y **Roles** (links directos). El filtro por grupo, sección o rama va en cada view, no en el drawer.

**Estructura:**

- **Grupos** (dropdown): primero los "Roles de Grupo X" (solo si es Jefe), después cada grupo con su código y nombre.
- **Secciones**, **Protagonistas**, **Equipos**, **Eventos**, **Roles**: links directos a una view general. Todavía no existen esas views generales, así que apuntan al inicio por ahora.
- **Equipos** se muestra solo si el usuario tiene acceso a alguna sección con pequeño equipo. **Roles** se muestra solo si el usuario es Jefe de algún grupo o sección.

**Decisiones:**

- **"Accesos" se renombra a "Roles" de cara al usuario**: el código sigue hablando de `accesos` (rutas, Controllers, Services), pero el menú dice "Roles" porque es lo que el usuario entiende.
- **Los roles de grupo van dentro del dropdown de Grupos**: al usuario le resulta natural buscar los roles de un grupo junto con los grupos.

## Encabezado de grupo en el inicio

El encabezado de cada grupo en `inicio.php` ahora tiene un fondo sutil (`rgba(69, 0, 158, 0.05)`) con borde izquierdo violeta, un badge con el número de grupo arriba del nombre, y el nombre en `h4 fw-bold texto-violeta`. Mucho más visible que el `home-section-title` anterior.

Los grupos se ordenan por código (antes era por nombre). Se cambió el `usort` en `SeccionService::misGrupos()`.

## Ícono de Manada

Se cambió de `bi-stars` a `bi-star-fill` en `Vista::iconoRama()`. Más claro y reconocible para Lobatos y Lobeznas.

## Listado de protagonistas (`protagonistas.php`)

Listado de protagonistas de una sección (`?id_seccion=`). Usa `ProtagonistaController::paginaListado`, que devuelve las fichas (`FichaProtagonista[]`) y las patrullas. Cada protagonista se muestra como una card (`protagonista-card` del legacy) con nombre, edad y patrulla, enlazada a su perfil.

**Filtro del lado del cliente** (sin JS propio como archivo aparte; el script va inline al final):
- Buscador por nombre/apellido (texto, filtra al escribir).
- Select de patrulla (si la rama tiene pequeño equipo), con opción "Sin [seisena/patrulla]".
- Mensaje "No se encontraron protagonistas con ese filtro" cuando no hay coincidencias.

**Sin protagonistas:** tarjeta con mensaje y un botón para dar de alta al primero (solo Responsable).

## Perfil de sección (`perfil_seccion.php`)

Perfil de una sección (`?id=`). Usa `SeccionController::paginaPerfil`: encabezado con la sección, la rama, el grupo y el rol del usuario; conteos (protagonistas, patrullas, sin patrulla) en tarjetas numéricas; links rápidos a Protagonistas, Patrullas, Eventos y Roles; distribución por patrulla con cantidad; y próximos eventos.

**Decisiones:**

- **Los conteos son sin datos personales** (`ConteoSeccion`), así que el Supervisor también los ve.
- **Links rápidos** según el rol: Protagonistas y Patrullas desde Ayudante, Roles solo para Jefe.
- **Roles** se muestra como "Roles" (no "Accesos") para el usuario, consistente con el drawer.

## Patrullas (`patrullas.php`)

Listado de patrullas de una sección (`?id_seccion=`). Usa `PatrullaController::paginaListado`. Cada patrulla es una `tarjeta-blanca-borde` con el nombre y los integrantes como lista enlazada a sus perfiles. Debajo, los protagonistas sin patrulla (si los hay). Modal para crear una nueva patrulla (Responsable). El nombre del equipo varía por rama (`nomenclatura`).

## Perfil de patrulla (`perfil_patrulla.php`)

Perfil de una patrulla (`?id=`). Usa `PatrullaController::paginaPerfil`. Muestra integrantes con botón de quitar, modal para agregar candidatos (el select muestra si ya están en otra patrulla) y menú ⋮ con Renombrar (modal, Responsable) y Eliminar (Jefe).

## Pantalla del grupo (`grupo.php`)

Pantalla de un grupo (`?id=`). Usa `SeccionController::paginaGrupo`. Encabezado con badge del código, nombre y rol. Tarjetas de secciones con color de rama, enlazadas al perfil si el usuario tiene acceso. Links a Eventos (Responsable de grupo) y Roles (Jefe de grupo). Próximos eventos del grupo.

## Eventos (`eventos.php` y `evento_form.php`)

**Listado** (`?id_grupo=` o `?id_seccion=`): usa `EventoController::paginaListado`. Cada evento muestra título, período, prioridad (badge con color), público/privado, descripción y alcance (todo el grupo o las secciones). Menú desplegable por evento: Editar, Desactivar/Reactivar y Eliminar, según permisos. Toggle para ver inactivos (Responsable). Los inactivos se muestran con opacidad reducida y badge "Inactivo".

**Formulario** (`?id_grupo=` para alta o `?id=` para edición): usa `EventoController::paginaFormulario`. Campos: título, descripción, fecha de inicio, fecha de fin (opcional), prioridad (select) y público (checkbox). Alcance: radio "Todo el grupo" / "Algunas secciones" con casillas que se habilitan/deshabilitan según la selección (JS inline).

## Roles (`accesos.php`)

Gestión de roles de un grupo (`?id_grupo=`) o de una sección (`?id_seccion=`). Usa `AccesoController::paginaListado`. Tabla con nombre, email y rol de cada usuario. El rol se cambia con un `<select>` que se envía al elegir (los `rolesAsignables` del Service determinan qué opciones tiene); si no se puede cambiar, muestra un badge estático. Botón de quitar con confirmación. Formulario para agregar a alguien por email, con select de rol (`rolesParaAgregar`). Todas las acciones recargan la página (`data-recargar`). La UI dice "Roles" consistente con el drawer.

## Solicitudes de alta (`solicitudes.php`)

Revisión de solicitudes pendientes (solo admin). Usa `SolicitudAltaController::paginaRevision`. Cada solicitud muestra: grupo pedido (código y nombre), quién la envió (nombre y email), fecha y las secciones con sus ramas. Aprobar con confirmación SweetAlert; rechazar con modal que pide el motivo (obligatorio). Ambas acciones redirigen a la misma pantalla (`Flash` muestra el resultado).

## Drawer: Equipos eliminado, Eventos y Roles por grupo

Se eliminó el link "Equipos" del drawer: a las patrullas se accede desde el perfil de la sección. Se movieron Eventos y Roles adentro del dropdown de Grupos: cada grupo muestra su link de Eventos (si tiene rol de grupo) y de Roles (si es Jefe). Se eliminaron los links sueltos de Eventos y Roles que apuntaban a `inicio.php`. Secciones y Protagonistas quedan como links temporales al inicio hasta que tengan sus propias pantallas de listado general.

## Miembros por sección en roles del grupo

En la pantalla de roles del grupo (`accesos.php?id_grupo=`) se agregó un acordeón "Miembros por sección" que muestra, por cada sección del grupo, los usuarios asignados directamente con su rol, en una tabla de solo lectura. Cada sección tiene un botón para ir a sus roles y editarlos. Los datos vienen de `AccesoService::accesosPorSeccionDeGrupo()`, método nuevo que reutiliza `AccesoSeccionRepository::listarUsuariosPorSeccion()` por cada sección.

## Nómina general de protagonistas

`protagonistas.php` ahora tiene dos modos: con `?id_seccion=` muestra las cards de la sección (el original); sin parámetro muestra la nómina/tabla general con todos los protagonistas de las secciones accesibles del usuario.

**Datos**: `ProtagonistaEnNomina` (clase vista nueva en `Repository/Vista/`) trae en una sola consulta con JOINs y subqueries: datos personales (DNI, nombre, apellido, fecha de nacimiento, año de ingreso), sección, grupo, rama, patrulla, etapa actual (la última lograda) y competencias desarrolladas vs. total de la rama. La consulta está en `ProtagonistaRepository::listarNomina()`.

**Filtros del lado del cliente**: buscador por nombre, selects de grupo, sección y rama (solo si hay más de un valor). Orden por columna (apellido, nombre, edad, competencias) con clic en el encabezado.

**Columna de competencias**: barra de progreso visual con el conteo X/Y.

**Flujo de datos**: `ProtagonistaService::nomina()` recorre los grupos del usuario (`SeccionService::misGrupos`), junta las secciones donde `puedeVerDetalle()` (Ayudante en adelante) y llama al repo con los ids. `ProtagonistaController::paginaNomina()` lo expone.

**Drawer**: "Protagonistas" ahora apunta a `protagonistas.php` (sin parámetro), la nómina general.

---

## Vista general de eventos

**Qué cambió**: `eventos.php` ahora tiene tres modos: sin parámetros (vista general), `?id_grupo=` (eventos del grupo) y `?id_seccion=` (eventos de la sección). Se agregó "Eventos" como link directo en el drawer.

**Por qué**: el usuario necesita ver todos sus eventos en un solo lugar sin entrar grupo por grupo. El link del drawer va a la vista general; los links "Eventos del Grupo X" del dropdown siguen llevando a la vista por grupo.

**Cómo funciona**:

- **`EventoService::eventosGenerales()`**: recorre los grupos del usuario (`SeccionService::misGrupos`). Si tiene rol de grupo, usa `pantallaGrupo`; si solo tiene acceso por secciones, arma una pantalla juntando los eventos de cada sección accesible sin repetir (deduplicación por id de evento). Devuelve `EventosGenerales` con un array de `{grupo, pantalla}` por grupo.

- **`EventosGenerales`** (`Service/Vista/`): clase vista que agrupa los resultados por grupo y ofrece un `total()` para saber si hay algo que mostrar.

- **`EventoController::paginaGeneral()`**: expone `eventosGenerales()` a la vista.

- **Vista general** (tabla compacta): muestra todos los eventos en una tabla con columnas Evento, Fecha, Prioridad, Alcance y Grupo. Incluye filtro por grupo (select, solo si hay más de uno) y acciones de editar/eliminar en un menú de tres puntos. Es más compacta que la vista detallada por grupo/sección, que mantiene las tarjetas con descripción completa.

- **Drawer**: se agregó "Eventos" como link directo a `eventos.php` (sin parámetros), entre Grupos y Secciones.

---

## Toggle cards/tabla en protagonistas de sección

**Qué cambió**: `protagonistas.php?id_seccion=` ahora tiene dos vistas: tarjetas (cards, la original) y tabla (nómina con progresión). Un botón de toggle en el encabezado alterna entre ambas; la preferencia se guarda en `localStorage`.

**Cómo funciona**: `ProtagonistaController::paginaListado` ahora también devuelve `'nomina'` (`ProtagonistaService::nominaPorSeccion`). El JS muestra/oculta los contenedores `#vistaCards` y `#vistaTabla` según el botón activo. Los filtros (buscador y patrulla) funcionan en ambas vistas al mismo tiempo.

---

## Roles en Mi cuenta

**Qué cambió**: `mi_cuenta.php` ahora muestra una sección "Mis roles" entre "Mis datos" y "Cambiar la contraseña", con todos los grupos y secciones del usuario y su rol en cada uno.

**Cómo funciona**: `UsuarioController::paginaMiCuenta` ahora también devuelve `'grupos'` (`SeccionService::misGrupos`). La vista los recorre mostrando el rol de grupo (si tiene) y cada sección con su rol y el ícono de rama.

---

## Insignias como pestaña en progresión

**Qué cambió**: en `progresion.php`, las insignias pasaron de estar debajo de las etapas a ser una pestaña paralela (Bootstrap nav-tabs). Las especialidades siguen debajo de ambas pestañas.

**Por qué**: mejor organización visual; el usuario ve solo lo que le interesa sin tener que scrollear hasta abajo. Si hay una insignia pendiente, la pestaña lo indica con un badge.

---

## Correcciones visuales menores

- **Etiqueta de nómina general**: el subtítulo "Nómina general de todas las secciones…" ahora hereda el color del banner (blanco) en lugar de usar `text-muted` (negro).
- **Botones del banner en grupo y eventos**: pasaron de `btn-outline-secondary` a `boton-primario` (degradado violeta, consistente con el resto de la app).
- **Botones "Nuevo" centrados**: los banners de `eventos.php` y `protagonistas.php` usan `align-items-center` para centrar verticalmente los botones.

---

## Tablas de nómina simplificadas

**Qué cambió**: las tablas de protagonistas se simplificaron en ambos modos.

**Tabla general** (`protagonistas.php` sin parámetros):
- Las columnas Apellido y Nombre se unificaron en una sola "Nombre" con formato "Apellido, Nombre".
- Se quitó la columna "Ingreso" (año de ingreso).
- La columna "Sección" muestra solo el nombre, sin la rama entre paréntesis, con el color primario de la rama como tinte del texto (`Vista::colorRama`).
- La columna "Competencias" muestra solo "X/Y" sin la barra de progreso.
- Los encabezados están centrados.

**Tabla de sección** (`protagonistas.php?id_seccion=`):
- Misma unificación de Apellido + Nombre.
- Se quitaron las columnas "Ingreso" y la barra de progreso de competencias.
- Los encabezados están centrados.

**Por qué**: la tabla anterior era demasiado ancha y repetía información visual (la barra de progreso no aportaba sobre "12/47"). El color de la sección da un vistazo rápido de la rama sin ocupar una columna.

---

## Vista general de eventos y eventos grupales

**Qué cambió**: se agregaron dos vistas de eventos accesibles desde el menú lateral:

1. **Eventos** (link directo en el drawer): `eventos.php` sin parámetros, muestra todos los eventos de los grupos y secciones accesibles en una tabla compacta con filtro por grupo.
2. **Eventos grupales** (dentro del dropdown de Grupos): `eventos.php?grupales=1`, la misma vista pero filtrada a los eventos de todo el grupo (sin los que son de secciones específicas).

**Cómo funciona**: `EventoService::eventosGenerales()` itera los grupos del usuario. Para quienes tienen rol de grupo usa `pantallaGrupo`; para quienes solo tienen acceso a secciones usa `pantallaDesdeSeccionesDelUsuario` (agrega los eventos de cada sección y deduplica por id). El resultado (`EventosGenerales`) agrupa los eventos por grupo. En el modo `?grupales=1`, el PHP filtra las filas con `esDeTodoElGrupo()` y calcula el total visible por separado.

---

## Vista general de secciones

**Qué cambió**: `secciones.php` muestra todas las secciones a las que el usuario tiene acceso, agrupadas por grupo, con las tarjetas de rama. Cada tarjeta es clickeable si el usuario tiene acceso directo a la sección. Accesible desde el link "Secciones" del menú lateral.

**Cómo funciona**: usa `SeccionController::paginaSecciones()` → `SeccionService::misGrupos()`, que ya devuelve los grupos con sus secciones y el nivel del usuario en cada una.

---

## Convención de nombres: secciones y grupos sin prefijo en la base

**Qué cambió**: las secciones se guardan solo con su nombre particular (ej. "Luna Llena", no "Manada Luna Llena") y los grupos sin el prefijo "Grupo" (ej. "Los Aromos", no "Grupo Scout Los Aromos"). Los prefijos se concatenan en la capa de presentación según el `id_rama` de la sección.

**Por qué**: separar el dato del formato de presentación. El prefijo de la sección depende de la rama ("Manada", "Unidad", "Comunidad Caminante", "Comunidad Rover") y no coincide con `rama.titulo_corto` ("Caminantes", "Rovers"), así que se necesita una función dedicada.

**Cómo funciona**: tres helpers en `Vista` (`src/Helper/Vista.php`):
- `prefijoSeccion(int $idRama)`: devuelve "Manada", "Unidad", "Comunidad Caminante" o "Comunidad Rover".
- `nombreCompletoSeccion(string $nombre, int $idRama)`: concatena prefijo + nombre.
- `nombreCompletoGrupo(string $nombre)`: concatena "Grupo " + nombre.

Todas las vistas, el menú lateral y `EventoService` (con alias `VistaHelper` para evitar colisión de namespace con `Service\Vista\*`) usan estos helpers. `SolicitudAltaService::enPantalla()` incluye `id_rama` en cada sección para que las vistas puedan calcular el nombre completo. Los datos de ejemplo (`datos_ejemplo.sql`) ya guardan solo el nombre particular.

---

## Tablas: nombre del grupo en vez del código

**Qué cambió**: en las tablas que mostraban el N° de grupo (código), ahora se muestra el nombre completo del grupo con `Vista::nombreCompletoGrupo()`.

**Por qué**: el nombre es más reconocible que el código numérico para el usuario.

---

## Menú lateral: links simplificados por grupo

**Qué cambió**: en el dropdown de Grupos del drawer se quitaron las entradas individuales "Eventos del Grupo X" y "Roles del Grupo X". Queda solo el link al grupo y "Eventos grupales" al final (si hay al menos un grupo).

**Por qué**: simplificar el menú; esas pantallas se acceden desde la vista del grupo.

---

## Imagen de marca T1: tokens de color (decisiones 1B y 2B)

**Qué cambió**: el violeta principal de Educarama pasa de `#791fbf` a `#4b1278`, y el violeta oscuro de `#5a1690` a `#360d57`. Los bordes, fondos y sombras que estaban escritos con rgba del violeta viejo pasan al nuevo: `rgba(75, 18, 120, X)` para bordes y fondos, y `rgba(54, 13, 87, X)` para sombras. `Vista::colorRama()` devuelve `#4b1278` cuando no hay rama. `public/js/educarama.js` ya no tiene hex propios: el color de los botones de SweetAlert2 se lee de `--violeta` y el de peligro de `--bs-danger`.

Los colores de rama (`.rama-lobatos`, `.rama-scouts`, `.rama-caminantes`, `.rama-rovers`, con los tres tonos oficiales de cada bandera más `--rama-contraste` y el par del banner) y las reglas que los usan ya estaban aplicados.

**Por qué**: el `#4b1278` es el Violeta oficial de Scouts de Argentina oscurecido dos pasos sin cambiar el tono. El oficial se sentía liviano como color dominante de la aplicación, y el círculo cromático no tiene un violeta más oscuro. Orquídea, Púrpura y Lila quedan con su valor oficial exacto.

**Cómo funciona**: toda la UI sale de variables de `:root` y de las clases `.rama-*`. Fuera de ese bloque no hay hex sueltos en `styles.css`, y tampoco en el JS. Cambiar un color es cambiar una sola variable.

**Arreglo de paso**: el hover de `.rama-card` y `.patrulla-card` se definía dos veces. La regla de más abajo pisaba el borde con `--violeta-oscuro`, así que al pasar el mouse el acento de la rama se volvía violeta. Se borró ese `border-color`: queda el de arriba, `var(--rama-oscuro)`.

---

## Imagen de marca T2: arreglos del CSS que arrastra el legacy

**Qué cambió**:
- `.barra-superior-inst` tenía `box-shadow: var(--borde-suave)`, que es un color y no una sombra, así que el navegador lo ignoraba. Pasa a `var(--sombra-card)`.
- El anillo de foco (`--foco`) pasa del amarillo al violeta. La excepción es el botón de hamburguesa, que está sobre el header violeta: ahí el violeta no se vería, así que conserva el amarillo.
- Los cuatro botones que usaban `btn-primary` pasan a `btn btn-sm boton-primario`: el "Guardar" de editar documento (`perfil_protagonista.php`), el `.btn-avanzar` (`gestionar_competencias.php`), "Registrar entrega" y "Empezar / Desarrollada" (`progresion.php`).
- `.btn-sm.boton-primario:hover` anula el `translateY(-3px)` del hover.

**Por qué**: el amarillo del foco daba 1,4:1 sobre blanco, y WCAG pide 3:1 para los indicadores de foco. El azul de Bootstrap no es un color de la paleta. En un botón chico, el salto del hover se nota demasiado.

**Cómo funciona**: se conserva `.btn` para que `btn-sm` siga dando el tamaño, y `.boton-primario` pisa el fondo con el degradado violeta. Ninguna vista usa `btn-primary`.

---

## Imagen de marca T3: tipografía

**Qué cambió**: se suma Ubuntu Condensed (`public/fonts/Ubuntu_Condensed/UbuntuCondensed-Regular.ttf`, servida en TTF). Las dos familias quedan como variables (`--fuente` y `--fuente-condensada`), y `body` y `h1`–`h6` usan `var(--fuente)` en vez de la lista con Segoe UI escrita a mano. La condensada se aplica a `.dato-etiqueta`, `.menu-titulo`, `.badge`, los encabezados de `.home-table` y los encabezados y celdas de las dos nóminas (`#tablaNomina`, `#tablaNominaSeccion`). Los encabezados de tabla van en mayúsculas, a 0.875rem y con `letter-spacing`.

Dos ajustes para no bajar de 12 px con la condensada: `.menu-titulo` pasa de 0.72rem a 0.75rem, y `.badge` usa `max(0.75em, 0.75rem)`. El 0.75em de Bootstrap daba 10,5 px dentro de un `.small`.

**Por qué**: el manual de marca usa Ubuntu, con Arial como sustitución. La condensada hace que la nómina y las etiquetas entren sin desbordar.

**Cómo funciona**: solo existe Ubuntu Condensed en peso 400, así que `font-synthesis: none` evita que el navegador fabrique un bold deformando la letra. Las reglas con `font-weight: 700` se ven en 400, y el énfasis queda a cargo de las mayúsculas y el `letter-spacing`. El `h1` del banner, las tarjetas, el menú lateral y el pie siguen en Ubuntu.

---

## Botón primario reactivo a la rama

**Qué cambió**: `.boton-primario` deja de ser siempre violeta. Sus colores salen de cuatro variables: `--boton-inicio`, `--boton-fin`, `--boton-texto` y `--boton-sombra`.
- **Sin rama** (`:root`): el Celeste oficial (`--celeste`) con texto `--texto-sobre-claro`.
- **Con rama** (`.rama-lobatos`, `.rama-scouts`, `.rama-caminantes`, `.rama-rovers`): el degradado violeta de siempre, con texto blanco.

**Por qué**: en las páginas sin rama todo es violeta (header, banner, menú), y un botón violeta se perdía. El Celeste oficial con texto blanco da 2,6:1, así que va con texto oscuro (7:1). En las páginas de rama el violeta ya se distingue del color de la rama, y un celeste ahí se confundiría con Caminantes.

**Cómo funciona**: la clase de rama está en el `<body>` (`$claseRama` en `cabecera.php`), así que redefine las variables y el botón cambia solo, sin tocar las vistas. El fin del degradado celeste es `color-mix()` del celeste con el texto oscuro (5,6:1): no suma ningún hex nuevo. Los cuadros de SweetAlert2 siguen con el botón violeta, porque van sobre blanco.

---

## Imagen de marca T4: logos de rama (decisión 3B)

**Qué cambió**: las tarjetas de sección de `inicio.php`, `grupo.php` y `secciones.php` muestran el logo oficial circular de la rama en vez del ícono de Bootstrap. Nuevo método `Vista::logoRama(?int $idRama): ?string`, que devuelve la URL de `views/assets/images/Logos/Logos_AreasYRamas/Logo_Circulo_*.png`, o null si la rama es desconocida.

**Por qué**: `Logo_Circulo_*` es el único set completo de logos de rama. El logo es la identidad completa de la rama, y en la tarjeta de sección hay lugar para mostrarlo.

**Cómo funciona**: si `logoRama()` devuelve null, la tarjeta cae en `Vista::iconoRama()`, que sigue igual como último respaldo. Con logo, el cuadrado lleva la clase `con-logo`: fondo blanco y borde `--rama-oscuro`, porque una marca oficial no puede ir sobre un fondo que le baje el contraste. La imagen lleva `alt=""` porque el nombre de la sección, al lado, ya dice la rama.

---

## Imagen de marca T5: íconos de concepto (decisión 3C)

**Qué cambió**: se usan los pictogramas oficiales de Scouts de Argentina, en Orquídea Oscura, desde `views/assets/images/Iconos/` (18 archivos). Hay tres métodos nuevos en `Vista`:
- `iconoOficial($slug)`: la URL de un ícono.
- `iconoAreaOficial($idArea)`: el ícono de cada una de las 10 áreas.
- `pictogramaRama($idRama)`: huella, rosa de los vientos, sol o cayado.

Dónde se usan:
- Pestañas de área en `gestionar_competencias.php`.
- Acordeón de especialidades en `progresion.php`.
- Documentos en `perfil_protagonista.php` (`pdf` / `png`).
- Links de patrullas (`banderin`) y eventos (`campamento`) en `perfil_seccion.php`.
- Pictograma de rama en "Mis roles" de `mi_cuenta.php` y en la columna Sección de la nómina general (`protagonistas.php`).

**Por qué**: alinear la iconografía con el sistema oficial. Orquídea Oscura llega a 5,5:1 sobre blanco.

**Cómo funciona**: si un método devuelve null, la vista cae en el ícono de Bootstrap (`iconoArea()` / `iconoRama()`). Los íconos llevan `alt=""` porque el texto de al lado ya dice qué son. `.icono-oficial` los alinea con el texto, a 1.25em.

Hay dos lugares que mantienen el ícono de Bootstrap. El menú lateral, por decisión del plan. Y el botón "Eventos" de `grupo.php`: es un `boton-primario` de color, y un activo oficial no puede ir sobre un fondo que le baje el contraste.

---

## Imagen de marca T6: insignias de progresión (decisión 4B)

**Qué cambió**: la pestaña de insignias de `progresion.php` muestra la imagen oficial de cada insignia de etapa en vez del ícono genérico. `insignia.imagen`, que ya existía, guarda solo el nombre del archivo: `educarama.sql` lo carga con 11 UPDATE después de crear las insignias. Hay dos helpers nuevos en `Vista`:
- `imagenInsignia($archivo)`: arma la URL en `views/assets/images/Insignias/Progresion/`.
- `imagenInsigniaCaminantes(?Elemento)`: arma la URL de una insignia de Caminantes según el elemento.

**Por qué**: las insignias son dato oficial del programa, y verlas como son ayuda al educador. La carpeta es un detalle de presentación, así que no se guarda en la base.

**Cómo funciona**:
- **Lobatos, Scouts y Rovers:** la imagen sale de la fila.
- **Caminantes (etapas 9 a 12):** la fila queda en NULL, porque la insignia depende del elemento elegido al confirmar la etapa (`etapa_lograda.elemento`). La vista arma un mapa etapa → elemento con las etapas logradas: con elemento, muestra `caminantes-<elemento>.png`; sin elemento, el ícono genérico.
- **Ciudadanía:** no tiene imagen.

Con imagen, el círculo (`.insignia-icono.con-imagen`) es solo un marco blanco, con borde del tono oscuro de la rama. La imagen nunca se altera: si la insignia está entregada o no lo dice el badge. `datos_ejemplo.sql` no cambia, porque `insignia` es estática y no la toca.

---

## Imagen de marca T7: marca y marco legal (decisión 5B)

**Qué cambió**:
- **Logo del header:** `cabecera.php` pedía `views/assets/images/Logo1.png`, que no existe en esa ruta, así que el logo no cargaba. Pasa a `Logos/Logos_Educarama/LogoBaseV4.png`, la versión 4 del logo.
- **Favicon:** se suma, con el mismo logo.
- **Pie:** decía "Plataforma Educarama - Scouts de Argentina". Ahora aclara que es un proyecto académico sin vinculación oficial, y atribuye el programa educativo y sus nombres a Scouts de Argentina Asociación Civil, con link a scouts.org.ar.
- **CSS:** `.footer-inst a` en blanco, para que el link se lea sobre el violeta.

**Por qué**: el pie anterior se leía como si fuera un producto oficial de la asociación. Las marcas oficiales son dato, no identidad de la app: el favicon y el header llevan solo el logo de Educarama.

**Cómo funciona**: son cambios en los includes comunes (`cabecera.php` y `pie.php`), así que aplican a todas las páginas. `Logo1.png` y `Logo1.jpg` quedan sin uso.

---

## Limpieza de CSS sin uso

**Qué cambió**: se borraron de `public/css/styles.css` las clases que ninguna vista ni JS usa:
- del dashboard del legacy: `.home-stats`, `.home-stat-card`, `.home-table`, `.home-action-btn`, `.home-alerts`, `.home-grid`, `.home-card`, `.birthday-list` y `.movements-list`, con sus media queries;
- `.punto-rama`, que quedó sin uso cuando el drawer dejó de listar secciones.

`.home-table th` también salió de las reglas de Ubuntu Condensed (T3).

**Por qué**: eran restos del dashboard del legacy. Mantenerlos obligaba a migrar colores (como en T1) en reglas que no se ven.

**Corrección (T6)**: las insignias con imagen ya no van dentro de un círculo con borde, y pasan de 52 a 88 px. La forma de la insignia cambia por rama (cuadrada en LyL, rombo en Scouts y Caminantes, rectángulo en Rovers), y el círculo las recortaba. Las que no tienen imagen siguen con el círculo y el ícono genérico.

---

## Formulario de protagonista: warning de PHP en "Año de ingreso"

**Qué cambió**: en el alta, `protagonista_form.php` leía `$protagonista->anioIngreso` con `$protagonista` en null. PHP imprimía el warning dentro del `value` del campo, y las comillas del mensaje cerraban el atributo, así que se veía código en la pantalla y quedaba roto el HTML que seguía. Ahora usa `?->`. Además, el campo de fecha de nacimiento (de protagonista y de familiar) lleva `max` con la fecha de hoy, para que el calendario no ofrezca fechas futuras.

---

## Segunda limpieza de CSS sin uso

**Qué cambió**: se revisaron todas las clases de `styles.css` contra las vistas, el PHP y el JS, y se borraron las que nadie usa:
- `.home-container`, `.home-welcome`, `.home-actions`;
- `.menu-titulo`, `.menu-detalle`;
- `.borde-orquidea`, `.tarjeta-lila`, `.pieza-insignia`;
- `.circulo-logo`, `.insignia-pequena`;
- `.ver-perfil`;
- `.patrulla-card` y `.patrulla-icono`: compartían reglas con `.rama-card` y `.rama-icono`, así que solo se quitó su selector.

**Por qué**: eran restos del legacy. Mantenerlos obligaba a migrar colores y tipografía en reglas que no se ven.

---

## Pie cortado en el celular (`accesos.php`)

**Qué cambió**: `.table-responsive { position: relative; }` en `styles.css`.

**Por qué**: el `<span class="visually-hidden">` del botón "Quitar" de la tabla de roles tiene `position: absolute`, que le pone Bootstrap. Como `.table-responsive` no estaba posicionada, ese span no quedaba recortado por su `overflow` y ensanchaba toda la página: 641 px en una pantalla de 375. La página se podía desplazar de costado, y el pie, que mide el 100% del ancho, se veía cortado.

**Cómo funciona**: con `position: relative`, la tabla es el bloque contenedor de sus elementos absolutos, y el desplazamiento lateral queda dentro de la tabla. Vale para todas las tablas. Se verificó a 375 px en 10 pantallas (accesos de grupo y de sección, las dos nóminas, eventos, perfil, progresión, competencias, inicio y grupo): ninguna supera el ancho de la pantalla.

---

## Fecha de nacimiento en tres listas

**Qué cambió**: en los formularios de protagonista y de familiar, la fecha de nacimiento deja de ser un `<input type="date">` y pasa a tres listas (día, mes abreviado y año, de este hacia atrás). Las arma `Vista::campoFecha($nombre, $id, $valor)`, y `public/js/educarama.js` junta las tres en el campo oculto que se envía.

**Por qué**: el calendario del navegador trae un botón "Hoy" que no se puede quitar. Por su posición se confunde con el de enviar, y un clic accidental cargaba la fecha de hoy como fecha de nacimiento. Además, el calendario arranca en el mes actual, lejos de cualquier fecha de nacimiento.

**Cómo funciona**:
- Las listas no tienen `name`. Lo que viaja es el oculto `fecha_nacimiento`, con `AAAA-MM-DD` o vacío, así que el Controller y el Service no cambian.
- El JS deshabilita los días que no existen en el mes elegido y marca la lista como inválida con `setCustomValidity` si la fecha está incompleta, no existe (31 de febrero) o es futura. Mientras sea inválida, el navegador no deja enviar el formulario.
- Los tres vacíos significa "sin fecha", porque el campo es opcional.
- En pantallas muy angostas (320 px) el año pasa a una segunda línea.
