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
