# Educarama ⚜️

> **Educación + Rama**: herramienta digital para el seguimiento de la progresión personal en el Movimiento Scout de Argentina.

![Logo de Educarama](views/assets/images/Logos/Logos_Educarama/LogoBaseV4.png)

## Sobre el proyecto

Educarama nace como proyecto universitario para la carrera de **Desarrollo de Software** en el Instituto Leopoldo Marechal. Su objetivo es resolver la fragmentación de la información en los grupos scouts, donde actualmente se depende de métodos manuales o planillas genéricas que dificultan el seguimiento pedagógico.

A diferencia de los sistemas oficiales centrados en lo administrativo, Educarama es una herramienta diseñada para el **trabajo de campo del educador**, enfocándose en la visualización clara y rápida del progreso de cada protagonista.

El proyecto se encuentra en la rama `refactor/v2`, que rehace la arquitectura de cero con PHP orientado a objetos y capas bien separadas (Model → Repository → Service → Controller → View).

---

## Funcionalidades

- **Gestión de grupos y secciones:** alta mediante solicitud revisada por un administrador; cada grupo tiene sus secciones (Manada, Unidad, Caminantes, Rovers).
- **Roles y accesos:** cuatro niveles (Supervisor, Ayudante, Responsable, Jefe) con herencia del Jefe de grupo a todas sus secciones.
- **Protagonistas:** alta, edición, pase de sección, asignación a patrullas y baja con cascada completa.
- **Progresión personal:** competencias por área y etapa, acciones (cumplidas/pendientes), cupo por etapa con avisos salteables, confirmación de etapas con tareas y elemento (Caminantes), e insignias registradas a mano.
- **Familiares:** vinculación de familiares a protagonistas, con detección de personas ya cargadas por DNI y contactos de emergencia.
- **Documentos:** subida de PDF, JPG y PNG hasta 5 MB por protagonista, con descarga segura a través de un controller (sin links directos al almacén).
- **Eventos:** grupales o por secciones, con prioridad, alcance, activación/desactivación y próximos eventos en el inicio.
- **Interfaz responsive:** menú lateral contextual, paleta por rama, fuente Ubuntu local y SweetAlert2 para confirmaciones.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.1+ con POO y `strict_types` |
| Base de datos | MySQL / MariaDB con PDO (consultas preparadas, sin emulación) |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| CSS | Bootstrap 5.3.3 + Bootstrap Icons 1.10.5 (CDN) |
| Alertas | SweetAlert2 11 (CDN) |
| Entorno | XAMPP (Apache + MySQL en localhost) |

No se usan frameworks, Composer ni npm. Las librerías de front se cargan por CDN con versión fija.

---

## Equipo de trabajo

- **Elías Zárate**
- **Camila Cardozo**
- **Cristian López**
- **Valentina Loza**
- **Germán Lucero**

---

## Estructura del proyecto

```
Educarama/
├── config/                      Configuración y arranque
│   ├── config.php               BASE_URL, ZONA_HORARIA, DB_*, ALMACEN_*
│   ├── config.local.php         Overrides locales (gitignored)
│   ├── bootstrap.php            Autoload propio (spl_autoload_register) + url() + e()
│   └── Database.php             PDO singleton
├── database/
│   ├── educarama.sql            Esquema completo (base educarama_v2)
│   ├── seeds/
│   │   └── catalogo_competencias.sql   186 competencias + 848 acciones
│   └── datos_ejemplo.sql        Datos de desarrollo (usuarios, grupos, progresión)
├── docs/
│   ├── descripcion-tecnica.md   Cómo está hecho: carpetas, capas, datos, roles, seguridad
│   ├── fundamentos-y-decisiones.md  Por qué está hecho así: cada decisión justificada
│   ├── explicacion-de-cambios.md    Registro de cada cambio de arquitectura
│   └── prompt-imagen-de-marca.md    Plan de alineación con la marca (hecho)
├── src/
│   ├── main.php                 Composition Root: arma todos los objetos (v2.1)
│   ├── Contenedor.php           Contenedor de dependencias (registrar / obtener)
│   ├── Almacen/                 Componente genérico de archivos (no depende del proyecto)
│   ├── Model/                   31 clases (una por tabla) + 5 enums en Enum/
│   ├── Repository/              Consultas a la base; devuelven Models
│   │   └── Vista/               Clases vista para consultas con JOIN
│   ├── Service/                 Reglas de negocio, validaciones, permisos
│   │   ├── Vista/               Clases para pantallas (resúmenes, fichas)
│   │   └── Excepcion/           Excepciones tipadas del dominio
│   ├── Controller/              Un controller por tema + Controlador base, Respuesta, Api, Pagina
│   └── Helper/                  Csrf, Sesion, Flash, Vista (helpers de presentación)
├── public/                      Punto de entrada web
│   ├── index.php                Redirección al inicio
│   ├── api.php                  Endpoint único para fetch (JSON)
│   ├── descargar.php            Descarga segura de documentos
│   ├── css/styles.css           Estilos (paleta, clases por rama, componentes)
│   ├── js/
│   │   ├── educarama.js         Fetch declarativo, CSRF, SweetAlert2, pestañas
│   │   ├── competencias.js      Pantalla de competencias del protagonista
│   │   └── solicitud_alta.js    Formulario dinámico de solicitud
│   └── fonts/                   Ubuntu (woff2) y Ubuntu Condensed (TTF), locales
├── storage/                     Archivos subidos (gitignored)
├── views/
│   ├── includes/                cabecera.php, pie.php, menu.php, error.php
│   ├── pages/                   Una vista por pantalla (20 archivos)
│   └── assets/images/           Logos, insignias, banderas de rama, íconos scouts
└── _legacy/                     Código viejo (solo lectura, referencia para portar)
```

---

## Instalación

### Requisitos

- [XAMPP](https://www.apachefriends.org/) con PHP 8.1+ y MySQL/MariaDB
- Navegador moderno (Chrome, Firefox, Edge)

### Pasos

1. Clonar el repositorio en la carpeta `htdocs` de XAMPP:
   ```bash
   cd C:/xampp/htdocs
   git clone <url-del-repo> Educarama
   cd Educarama
   git checkout refactor/v2.1
   ```

2. Importar la base desde phpMyAdmin o la terminal. No hace falta crearla antes: `educarama.sql` crea `educarama_v2`. Importar en orden:
   1. `database/educarama.sql`
   2. `database/seeds/catalogo_competencias.sql`
   3. `database/datos_ejemplo.sql` *(solo para desarrollo)*

3. Configuración local (solo si hace falta): si tu MySQL no es el de XAMPP por defecto (`root` sin contraseña), crear `config/config.local.php` con lo distinto:
   ```php
   <?php
   define('DB_PASS', 'mi_contraseña');
   ```
   Ese archivo no se sube a git.

4. Iniciar Apache y MySQL desde el panel de XAMPP y entrar a:
   ```
   http://localhost/Educarama/
   ```

### Usuarios de prueba

Contraseña de todos: `educarama123` (excepto `ejemplo@gmail.com` que usa `ejemplo`).

| Email | Rol |
|---|---|
| `jefe.grupo1@ejemplo.test` | Jefe del grupo 1 |
| `responsable.manada@ejemplo.test` | Responsable de Manada (grupo 1) |
| `ayudante.unidad@ejemplo.test` | Ayudante de Unidad (grupo 1) |
| `supervisor.grupo1@ejemplo.test` | Supervisor del grupo 1 |
| `supervisor.caminantes@ejemplo.test` | Supervisor grupo 1 + Responsable Caminantes |
| `jefe.grupo2@ejemplo.test` | Jefe del grupo 2 |
| `admin@ejemplo.test` | Administrador (revisa solicitudes) |
| `nuevo@ejemplo.test` | Sin accesos, con solicitud pendiente |
| `ejemplo@gmail.com` | Admin + Jefe de los dos grupos |

---

## Datos oficiales del programa Scout

| Rama | Nombre común | Especialidades | Acciones | Etapas |
|---|---|---|---|---|
| Lobatos y Lobeznas | Manada | Sí | Sí | Pata Tierna, Saltador/a, Rastreador/a, Cazador/a |
| Scouts | Unidad | Sí | Sí | Pistas, Senda, Rumbo, Travesía |
| Caminantes | Comunidad Caminante | No | Sí | 4 etapas × elemento (agua, tierra, fuego, aire) |
| Rovers | Comunidad Rover | No | No | Encuentro, Compromiso, Proyección |

Catálogo cargado: **186 competencias** y **848 acciones** distribuidas en 4 ramas y 10 áreas.

---

## Licencia

Proyecto universitario. Todos los derechos reservados.
