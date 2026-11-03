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
