# Educarama — alineación con la imagen de marca

Prompt de trabajo para el paso 5 (Views). Complementa a `CLAUDE.md`: todo lo que dice ese archivo sigue valiendo.

**Este archivo es la fuente de los cambios.** Cada tarea trae el código listo para aplicar. Las tareas hechas quedan documentadas acá igual, porque son la referencia de por qué cada color es el que es.

**División de trabajo:** los archivos de imagen los copia y renombra Elías a mano (ver "Archivos que se preparan a mano"); Claude Code escribe código y asume que esos archivos ya están en su lugar. Los nombres de origen tienen acentos, espacios y una carpeta mal escrita en el disco, y son 18 archivos entre 429: copiarlos a mano es más rápido y más seguro que automatizarlo.

Auditoría completa que origina este plan: _Auditoría de imagen de marca — Educarama_ (doc del proyecto).

## Cómo trabajar

Lo de `CLAUDE.md` ("Cómo trabajar conmigo"), más:

- Esto **no es un rediseño**: es alinear lo que ya existe con el manual de marca de Scouts de Argentina. No se inventan pantallas ni componentes nuevos.
- Ningún color, fuente ni ícono nuevo que no esté en este archivo. Si hace falta uno, preguntar.
- Una tarea por vez. Al terminar cada una, entrada en `docs/explicacion-de-cambios.md` y tildar su casilla acá.
- Las tareas están en orden y T1 es la base de todas: no empezar por otra.

## Decisiones tomadas (cerradas)

| #          | Decisión                                                                                                        |
| ---------- | --------------------------------------------------------------------------------------------------------------- |
| 1B         | Los violetas de Educarama salen del círculo cromático SAAC; el primario va oscurecido dos pasos para darle peso |
| 2B         | Los colores de rama pasan a los tres tonos oficiales de cada bandera                                            |
| 3B         | Los íconos de rama pasan a `Logo_Circulo_*`                                                                     |
| 3C         | Los íconos de concepto pasan a `Icons_Principal` (18 elegidos, en Orquídea Oscura)                              |
| 4B         | Las insignias de progresión se implementan ahora, con los PNG de `Insignias/Progresion/`                        |
| 5B         | Aviso de proyecto académico, isotipo aislado en el header, máximo 2 logos por pantalla                          |
| Caminantes | La insignia de cada etapa sale de `etapa_lograda.elemento`: se muestra la que eligió el protagonista            |
| Condensed  | Ubuntu Condensed se sirve en TTF, sin convertir a woff2: no se suma una herramienta de conversión al proyecto   |
| Archivos   | Las imágenes las copia y renombra Elías a mano; Code solo escribe código                                        |

## Archivos que se preparan a mano

Dos carpetas. Hasta que existan, T5 y T6 no se pueden probar.

### `views/assets/images/Iconos/` — 18 archivos

Origen: `views/assets/images/Logos/Icons_Principal/Principa-Positivo/`. **Todos en la variante Orquídea Oscura**, que es el violeta del círculo cromático y da 5,5:1 sobre blanco. Nombre de origen: `Icono Orquídea Oscura_<Concepto>.png`. No se usan los negativos: su cuadrado de color no corresponde a ninguna rama.

**Áreas de desarrollo (10)**

| Concepto en el nombre de origen | Se copia como        | Área                         |
| ------------------------------- | -------------------- | ---------------------------- |
| `Empatía`                       | `empatia.png`        | 1 · Salud y Bienestar        |
| `OSD 17`                        | `ods-17.png`         | 2 · Paz y Desarrollo         |
| `Árbol`                         | `arbol.png`          | 3 · Ambiente                 |
| `Construcciones Scouts`         | `construcciones.png` | 4 · Habilidades para la Vida |
| `Montaña`                       | `montania.png`       | 5 · Corporalidad             |
| `Ideas`                         | `ideas.png`          | 6 · Creatividad              |
| `Linterna`                      | `linterna.png`       | 7 · Carácter                 |
| `Corazón - Afecto`              | `corazon.png`        | 8 · Afectividad              |
| `Diálogo`                       | `dialogo.png`        | 9 · Sociabilidad             |
| `Espiritualidad`                | `espiritualidad.png` | 10 · Espiritualidad          |

**Ramas (4)**

| Concepto en el nombre de origen | Se copia como             | Rama                                                                      |
| ------------------------------- | ------------------------- | ------------------------------------------------------------------------- |
| `Huella`                        | `huella.png`              | Lobatos y Lobeznas (oficial: el manual la asigna a Manada)                |
| `Rosa de los Vientos`           | `rosa-de-los-vientos.png` | Scouts (su bandera y sus cuatro insignias forman una rosa de los vientos) |
| `Sol`                           | `sol.png`                 | Caminantes (el Sol de Mayo del centro de la Chakana)                      |
| `Cayado`                        | `cayado.png`              | Rovers (oficial: el manual lo asigna a Rovers)                            |

**Dominio (4)**

| Concepto en el nombre de origen | Se copia como    | Para qué                                                               |
| ------------------------------- | ---------------- | ---------------------------------------------------------------------- |
| `Banderín`                      | `banderin.png`   | Patrullas y pequeños equipos (oficial: el manual lo asigna a Patrulla) |
| `Campamento`                    | `campamento.png` | Eventos                                                                |
| `Archivo PDF`                   | `pdf.png`        | Documentos del protagonista                                            |
| `Archivo PNG`                   | `png.png`        | Documentos, cuando es imagen                                           |

Al renombrar: todo en minúsculas, sin acentos, sin espacios y sin ñ (`montania`, no `montaña`). El archivo de ODS 17 viene con una errata en el original (`OSD`); el slug la corrige.

### `views/assets/images/Insignias/Progresion/` — renombrar en el lugar

Los 16 archivos ya están ahí, con espacios y acentos. Se renombran:

| Nombre actual                                               | Pasa a                      |
| ----------------------------------------------------------- | --------------------------- |
| `Progresion 1 Rama Lobatos y Lobeznas_Lobo Pata Tierna.png` | `lobatos-1-pata-tierna.png` |
| `Progresion 2 Rama Lobatos y Lobeznas_Lobo Saltador.png`    | `lobatos-2-saltador.png`    |
| `Progresion 3 Rama Lobatos y Lobeznas_Lobo Rastreador.png`  | `lobatos-3-rastreador.png`  |
| `Progresion 4  Rama Lobatos y Lobeznas_Lobo Cazador.png`    | `lobatos-4-cazador.png`     |
| `Progresion 5 Rama Lobatos y Lobeznas_Completa.png`         | `lobatos-completa.png`      |
| `Progresiones Rama Scouts_Pistas.png`                       | `scouts-1-pistas.png`       |
| `Progresiones Rama Scouts_Senda.png`                        | `scouts-2-senda.png`        |
| `Progresiones Rama Scouts_Rumbo.png`                        | `scouts-3-rumbo.png`        |
| `Progresiones Rama Scouts_Travesía.png`                     | `scouts-4-travesia.png`     |
| `Progresiones Rama Caminantes_Agua.png`                     | `caminantes-agua.png`       |
| `Progresiones Rama Caminantes_Aire.png`                     | `caminantes-aire.png`       |
| `Progresiones Rama Caminantes_Fuego.png`                    | `caminantes-fuego.png`      |
| `Progresiones Rama Caminantes_Tierra.png`                   | `caminantes-tierra.png`     |
| `Progresiones Rama Rovers_Encuentro.png`                    | `rovers-1-encuentro.png`    |
| `Progresiones Rama Rovers_Compromiso.png`                   | `rovers-2-compromiso.png`   |
| `Progresiones Rama Rovers_Proyección.png`                   | `rovers-3-proyeccion.png`   |

`lobatos-completa.png` es el cuadrado con las cuatro etapas unidas: no corresponde a ninguna fila de `insignia` y por ahora no se usa.

## Reglas fijas

### Color

Toda la UI sale de variables. **Ningún hex suelto fuera de `:root` y de las clases de rama**, ni en el CSS ni en las vistas.

El violeta principal (`#4b1278`) es el Violeta oficial `#791fbf` oscurecido dos pasos, manteniendo el tono: el oficial se sentía liviano como color dominante de la aplicación y el círculo cromático no tiene ningún violeta más oscuro. Orquídea, Púrpura y Lila quedan con su valor oficial exacto.

Cada rama define cuatro tonos y su banner. `--rama-primario` es el tono vivo (puntos, íconos, acentos); `--rama-contraste` es el único legible como texto sobre blanco; el banner usa el par medido para que su texto pase 4,5:1.

| Clase              | primario  | secundario | oscuro    | contraste | banner (inicio → fin) | texto     | Contraste |
| ------------------ | --------- | ---------- | --------- | --------- | --------------------- | --------- | --------- |
| `.rama-lobatos`    | `#f5c600` | `#ec9d00`  | `#ea8e11` | `#a06a00` | primario → oscuro     | `#22003f` | 7,3:1     |
| `.rama-scouts`     | `#65ac46` | `#588d3c`  | `#406f33` | `#406f33` | primario → secundario | `#22003f` | 4,6:1     |
| `.rama-caminantes` | `#08a2c7` | `#0a7b96`  | `#116483` | `#116483` | secundario → oscuro   | `#ffffff` | 4,9:1     |
| `.rama-rovers`     | `#e73333` | `#b81d1b`  | `#931b25` | `#931b25` | secundario → oscuro   | `#ffffff` | 6,5:1     |

`--rama-contraste` de Lobatos es el amarillo oficial oscurecido: el original da 1,4:1 sobre blanco. `Vista::colorRama()` devuelve exactamente esos cuatro valores.

**Por qué el texto del banner no es el mismo en las cuatro ramas:** el amarillo y el verde oficiales son claros y el blanco encima no llega a 4,5:1; el celeste y el rojo son oscuros y el texto oscuro no llega. Igualarlos obligaría a oscurecer Lobatos hasta un marrón y Scouts hasta un verde casi negro, que ya no son el color de la rama. El color del texto lo decide el contraste, no la simetría.

### Tipografía

Dos familias: Ubuntu para todo, Ubuntu Condensed para etiquetas, encabezados de tabla, celdas de datos y badges. **Nunca condensada** para texto corrido, para el `h1` de la página ni por debajo de 12 px. Solo existe Condensed 400: el énfasis va por mayúsculas y `letter-spacing`, nunca con un bold sintético del navegador.

### Marcas oficiales

**Las marcas oficiales son dato, no marca de la app.**

- Sí: insignias de progresión, logos y banderas de rama como identidad de sección, íconos del sistema iconográfico, y una mención de atribución en el pie o en "Acerca de".
- No: favicon, logo del header, portada del login, pantallas de carga, encabezados de PDF o mails.
- Nunca el logo de Educarama y el imagotipo de SAAC en un mismo conjunto, ni una marca oficial recoloreada.
- Un activo oficial va siempre sobre un fondo que no le baje el contraste, y se usa tal como viene: ni teñido, ni en escala de grises, ni con filtros. Un estado de la interfaz (pendiente, inactivo) se comunica con texto o con un badge, no alterando la imagen.
- El logo de la app es `LogoBaseV4` (la versión oscura, sin volutas al pie del tallo).

### Las tres formas de mostrar una rama

Conviven y no se pisan:

1. **Logo circular oficial** (`Vista::logoRama`, T4): en las tarjetas de sección, donde hay espacio. Es la identidad completa.
2. **Pictograma** (`Vista::pictogramaRama`, T5): donde el logo no entra o sería demasiado — una celda de tabla, un badge, una línea de texto.
3. **Ícono de Bootstrap** (`Vista::iconoRama`): último respaldo, si una rama no tiene activo oficial.

## Tareas

### T1 — Tokens de color (1B + 2B)

**✅ Hecha.** Es la base de todo lo demás. (Las clases de rama ya estaban; faltaba el violeta `#4b1278`, sus rgba y el hex de `educarama.js`.)

En `public/css/styles.css`, reemplazar el bloque `:root` y las cuatro clases de rama (todo lo que va desde `:root {` hasta el cierre de `.patrulla-card:hover`) por:

```css
:root {
  /* ----- Marca propia de Educarama -------------------------------------
     Violetas del círculo cromático oficial de Scouts de Argentina. */
  --violeta: #4b1278; /* Violeta oficial (#791fbf) oscurecido dos pasos */
  --orquidea: #874dbd; /* Orquídea oscura */
  --purpura: #a539b6; /* Púrpura */
  --lila: #b85bee; /* Lila */
  --violeta-oscuro: #360d57; /* derivado de --violeta: degradados, hover, estado activo */

  /* ----- Resto de la paleta oficial ----- */
  --celeste: #00aaf2; /* Celeste */
  --amarillo: #fdd617; /* Amarillo tráfico */
  --verde-logro: #60b134; /* Verde amarillento: estados "logrado / desarrollada" */

  /* Solo para el imagotipo oficial de Scouts de Argentina; nunca para la interfaz. */
  --celeste-imagotipo: #0081c8;
  --amarillo-imagotipo: #f7cf00;

  /* Fondo general. No es del círculo cromático: viene del legacy. */
  --beige-fondo: #e0dfb6;

  /* ----- Neutros ----- */
  --blanco: #ffffff;
  --texto-principal: #2f2440;
  --texto-suave: #6f647c;
  --texto-tenue: #9a90a8;
  /* Texto oscuro para los fondos claros de rama (amarillos y verdes). */
  --texto-sobre-claro: #22003f;

  /* Tintes derivados del violeta, para bordes, líneas y fondos suaves. */
  --violeta-borde: #dfc9ff;
  --violeta-linea: #eadcff;
  --violeta-fondo: #f1eef5;
  --gris-linea: #d9d2e3;
  --gris-pista: #ece7f4; /* fondo de la barra de progreso */

  --borde-suave: rgba(75, 18, 120, 0.18);
  --sombra-suave: 0 10px 30px rgba(54, 13, 87, 0.12);
  --sombra-card: 0 8px 20px rgba(54, 13, 87, 0.1);

  /* ----- Variables de rama (sin rama: la marca propia) -------------------
     --rama-primario   tono vivo: puntos, íconos, acentos.
     --rama-secundario tono medio: degradados.
     --rama-oscuro     tono profundo: bordes gruesos y fin del degradado.
     --rama-contraste  el único legible como texto sobre blanco (4,5:1). */
  --rama-primario: var(--violeta);
  --rama-secundario: var(--orquidea);
  --rama-oscuro: var(--violeta-oscuro);
  --rama-contraste: var(--violeta);

  /* Banner de título de cada página (.barra-superior-inst): su degradado y el
     color del texto. El par está elegido para que el texto pase 4,5:1 en el
     extremo más desfavorable del degradado. */
  --banner-inicio: var(--violeta);
  --banner-fin: var(--violeta-oscuro);
  --banner-texto: var(--blanco);

  /* Medidas */
  --sidebar-width: 270px;
  --navbar-height: 72px;
  --footer-height: 50px;
  --foco: 3px solid var(--amarillo);
}

/* Colores de rama: los tres tonos oficiales de cada bandera de Scouts de
   Argentina. El contraste anotado es el del texto del banner contra el
   extremo más desfavorable de su degradado. */

/* Lobatos y Lobeznas — amarillo (Pantone 7406 C, 124 C, 1375 C). 7,3:1 */
.rama-lobatos {
  --rama-primario: #f5c600;
  --rama-secundario: #ec9d00;
  --rama-oscuro: #ea8e11;
  --rama-contraste: #a06a00; /* derivado: el amarillo oficial no llega a 4,5:1 sobre blanco */
  --banner-inicio: var(--rama-primario);
  --banner-fin: var(--rama-oscuro);
  --banner-texto: var(--texto-sobre-claro);
}

/* Scouts — verde (Pantone 7489 C, 370 C, 7743 C). 4,6:1 */
.rama-scouts {
  --rama-primario: #65ac46;
  --rama-secundario: #588d3c;
  --rama-oscuro: #406f33;
  --rama-contraste: #406f33;
  --banner-inicio: var(--rama-primario);
  --banner-fin: var(--rama-secundario);
  --banner-texto: var(--texto-sobre-claro);
}

/* Caminantes — azul celeste (Pantone 3125 C, 3145 C, 2392 C). 4,9:1 */
.rama-caminantes {
  --rama-primario: #08a2c7;
  --rama-secundario: #0a7b96;
  --rama-oscuro: #116483;
  --rama-contraste: #116483;
  --banner-inicio: var(--rama-secundario);
  --banner-fin: var(--rama-oscuro);
  --banner-texto: var(--blanco);
}

/* Rovers — rojo (Pantone 1788 C, 2350 C, 7623 C). 6,5:1 */
.rama-rovers {
  --rama-primario: #e73333;
  --rama-secundario: #b81d1b;
  --rama-oscuro: #931b25;
  --rama-contraste: #931b25;
  --banner-inicio: var(--rama-secundario);
  --banner-fin: var(--rama-oscuro);
  --banner-texto: var(--blanco);
}

/* Acento de color en las cards. Usa el tono oscuro: el amarillo de Manada no
   se distinguiría del blanco. */
.protagonista-card,
.rama-card,
.patrulla-card {
  border-left: 8px solid var(--rama-oscuro);
}
.protagonista-card:hover,
.rama-card:hover,
.patrulla-card:hover {
  border-color: var(--rama-oscuro);
}
```

**En el resto del archivo** (de ahí para abajo, nunca adentro de `:root`), reemplazar cada hex y cada rgba por su variable:

| Valor viejo                                   | Pasa a                                   |
| --------------------------------------------- | ---------------------------------------- |
| `#ffffff`, `white`                            | `var(--blanco)`                          |
| `#45009e`, `#4b00a8`, `#5800a8`, `#6110ad`    | `var(--violeta)`                         |
| `#320071`, `#3f007a`                          | `var(--violeta-oscuro)`                  |
| `#a068d4`                                     | `var(--orquidea)`                        |
| `#22003f`                                     | `var(--texto-sobre-claro)`               |
| `#2b1746`, `#2f2440`                          | `var(--texto-principal)`                 |
| `#6f647c`                                     | `var(--texto-suave)`                     |
| `#9a90a8`                                     | `var(--texto-tenue)`                     |
| `#dfc9ff`                                     | `var(--violeta-borde)`                   |
| `#eadcff`                                     | `var(--violeta-linea)`                   |
| `#f1eef5`                                     | `var(--violeta-fondo)`                   |
| `#d9d2e3`                                     | `var(--gris-linea)`                      |
| `#ece7f4`                                     | `var(--gris-pista)`                      |
| `#e0dfb6`                                     | `var(--beige-fondo)`                     |
| `#00a651`                                     | `var(--verde-logro)`                     |
| `rgba(50, 0, 113, X)`, `rgba(88, 0, 168, X)`  | `rgba(54, 13, 87, X)` (sombras)          |
| `rgba(69, 0, 158, X)`, `rgba(97, 16, 173, X)` | `rgba(75, 18, 120, X)` (bordes y fondos) |
| `rgba(0, 166, 81, 0.06)`                      | `rgba(96, 177, 52, 0.08)`                |

Reglas que además cambian de variable:

- `.circulo-logo`, `.insignia-pequena`: borde a `var(--rama-oscuro)`.
- `.tabs-inst .nav-link.active`: `border-bottom` a `var(--rama-contraste)`.
- `.ver-perfil`: color a `var(--rama-contraste)`.
- `.rama-icono`, `.patrulla-icono`, `.insignia-icono`: fondo al par del banner (`var(--banner-inicio)` → `var(--banner-fin)`) y el ícono (`i`) a `var(--banner-texto)`. Antes era violeta sobre el color de la rama y en Rovers no se leía.
- `.home-welcome`: fondo al par del banner, `color: var(--banner-texto)`, y `h2` y `p` con `color: inherit`. Así deja de tener `#22003f` fijo, que en Rovers daba 3,77:1.
- `.etapa-card.en-curso`: `border-left-color` a `var(--rama-oscuro)`.
- `.encabezado-grupo`: fondo `rgba(75, 18, 120, 0.06)` y borde `var(--violeta)`.

Borrar `--celeste-inst` y `--lila-cards` (nunca se usaban) y los pastel `--scout-*-claro` junto con los `--scout-*`.

En `src/Helper/Vista.php`, los dos métodos de color:

```php
    /**
     * Clase CSS de la rama. Define sus cuatro colores en public/css/styles.css
     * (--rama-primario, --rama-secundario, --rama-oscuro y --rama-contraste) y
     * el degradado del banner. Sale del id de la rama, nunca del nombre de la
     * sección (bug del legacy: deducirla con strpos).
     * Sin rama (o un id desconocido), '' = la paleta violeta de siempre.
     */
    public static function claseRama(?int $idRama): string
    {
        return match ($idRama) {
            1       => 'rama-lobatos',
            2       => 'rama-scouts',
            3       => 'rama-caminantes',
            4       => 'rama-rovers',
            default => '',
        };
    }

    /**
     * Color de la rama para usar como texto sobre blanco, fuera de la clase
     * rama-* (ej. el nombre de la sección en la nómina). Es el mismo valor que
     * --rama-contraste: el tono de cada bandera que llega a 4,5:1 sobre blanco.
     * El amarillo de Lobatos y Lobeznas no llega, así que se usa oscurecido.
     */
    public static function colorRama(?int $idRama): string
    {
        return match ($idRama) {
            1       => '#a06a00', // Lobatos y Lobeznas (amarillo oficial oscurecido hasta 4,6:1)
            2       => '#406f33', // Scouts (Pantone 7743 C)
            3       => '#116483', // Caminantes (Pantone 2392 C)
            4       => '#931b25', // Rovers (Pantone 7623 C)
            default => '#4b1278', // Violeta de Educarama
        };
    }
```

Buscar en `views/` las clases viejas (`rama-manada`, `rama-unidad`, `rama-rover`) por si alguna está escrita a mano en vez de salir de `Vista::claseRama()`.

**Verificación:** después del cambio, buscar `#` seguido de seis dígitos hexadecimales en `styles.css` fuera del bloque `:root` y de las clases `.rama-*`. No debe quedar ninguno.

### T2 — Arreglos del CSS que arrastra el legacy

- [x] `.barra-superior-inst` usa `box-shadow: var(--borde-suave)`, que es un color y no una sombra: pasa a `var(--sombra-card)`.
- [x] El anillo de foco (`--foco`) es amarillo y sobre blanco da 1,4:1, muy por debajo del 3:1 que pide WCAG. Pasa a `3px solid var(--violeta)`, con el `outline-offset` que ya está.
- [x] Quedan botones con el azul de Bootstrap, que no es un color de la paleta. Buscar `btn-primary` en `views/` y reemplazarlo por `btn btn-sm boton-primario` (se conserva `.btn` para que `btn-sm` siga dando el tamaño; `.boton-primario` pisa el fondo). Son cuatro: el "Guardar" de editar documento en `perfil_protagonista.php`, el `.btn-avanzar` de `gestionar_competencias.php`, el "Registrar entrega" de insignias y el "Empezar / Desarrollada" de especialidades, los dos en `progresion.php`.
- [x] En un botón chico el `translateY(-3px)` del hover salta demasiado. Agregar:
  ```css
  .btn-sm.boton-primario:hover {
    transform: none;
  }
  ```

**Criterio:** el banner tiene sombra, el foco se ve sobre blanco y una búsqueda de `btn-primary` en `views/` no devuelve nada.

### T3 — Tipografía

**✅ Hecha.** (Además: `.menu-titulo` sube a 0.75rem y `.badge` a `max(0.75em, 0.75rem)` para no bajar de 12 px.)

Agregar el `@font-face` de la condensada junto a los ocho de Ubuntu. **Se sirve en TTF, sin convertir.**

```css
@font-face {
  font-family: "Ubuntu Condensed";
  src: url("../fonts/Ubuntu_Condensed/UbuntuCondensed-Regular.ttf")
    format("truetype");
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
```

Las dos familias como variables, en `:root`:

```css
--fuente: "Ubuntu", Arial, sans-serif;
--fuente-condensada: "Ubuntu Condensed", "Arial Narrow", Arial, sans-serif;
```

`body` y `h1–h6` pasan a `font-family: var(--fuente)`, en vez de la lista escrita a mano con Segoe UI. El manual pide Arial como fuente de sustitución.

Dónde va la condensada:

```css
/* Ubuntu Condensed solo existe en peso 400: font-synthesis evita que el
   navegador invente un bold deformando la letra. El énfasis va por
   mayúsculas y letter-spacing. */
.dato-etiqueta,
.menu-titulo,
.badge,
.home-table th,
#tablaNomina th,
#tablaNominaSeccion th,
#tablaNomina td,
#tablaNominaSeccion td {
  font-family: var(--fuente-condensada);
  font-synthesis: none;
}

#tablaNomina th,
#tablaNominaSeccion th,
.home-table th {
  font-size: 0.875rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
```

`.dato-etiqueta` y `.menu-titulo` ya vienen en mayúsculas con `letter-spacing`: solo cambia la familia.

No tocar: el `h1` del banner, los títulos de tarjeta, el texto de las tarjetas, el menú lateral ni el pie. Todo eso sigue en Ubuntu.

**Criterio:** la nómina entra sin desbordar; ningún bold sintético sobre la condensada; nada de texto corrido en condensada.

### T4 — Logos de rama (3B)

**✅ Hecha.** (También en `grupo.php`, que armaba la tarjeta igual.)

El set `Logo_Circulo_*` es el único completo de `Logos_AreasYRamas` (las cuatro ramas más Adultos). Los archivos ya están en `views/assets/images/Logos/Logos_AreasYRamas/` con esos nombres exactos.

Método nuevo en `src/Helper/Vista.php`:

```php
    /**
     * URL del logo oficial de la rama. Se usa la versión en círculo, el único
     * set completo de Logos_AreasYRamas. Devuelve null si no hay rama o el id
     * es desconocido, para que la vista caiga en iconoRama().
     */
    public static function logoRama(?int $idRama): ?string
    {
        $archivo = match ($idRama) {
            1       => 'Logo_Circulo_Lobatos-y-Lobeznas.png',
            2       => 'Logo_Circulo_Scouts.png',
            3       => 'Logo_Circulo_Caminantes.png',
            4       => 'Logo_Circulo_Rovers.png',
            default => null,
        };

        return $archivo === null
            ? null
            : url('views/assets/images/Logos/Logos_AreasYRamas/' . $archivo);
    }
```

`iconoRama()` no se toca: sigue devolviendo la clase de Bootstrap y queda como último respaldo.

En `inicio.php` y en `secciones.php` la tarjeta se arma concatenando `$contenido`. Reemplazar esa asignación, que hoy es

```php
$contenido = '<div class="rama-icono"><i class="bi ' . e(Vista::iconoRama($seccion->rama->id)) . '" aria-hidden="true"></i></div>'
    . '<div><h3>' . e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) . '</h3>'
    . '<p>' . e($seccion->rama->titulo) . ($seccion->nivel !== null ? ' · ' . e($seccion->nivel->etiqueta()) : '') . '</p></div>';
```

por

```php
// El logo oficial de la rama, o el ícono de Bootstrap si la rama no tiene uno.
$logoRama = Vista::logoRama($seccion->rama->id);
$marcaRama = $logoRama !== null
    ? '<div class="rama-icono con-logo"><img src="' . e($logoRama) . '" alt=""></div>'
    : '<div class="rama-icono"><i class="bi ' . e(Vista::iconoRama($seccion->rama->id)) . '" aria-hidden="true"></i></div>';

$contenido = $marcaRama
    . '<div><h3>' . e(Vista::nombreCompletoSeccion($seccion->seccion->nombre, $seccion->rama->id)) . '</h3>'
    . '<p>' . e($seccion->rama->titulo) . ($seccion->nivel !== null ? ' · ' . e($seccion->nivel->etiqueta()) : '') . '</p></div>';
```

`alt=""` porque el nombre de la sección, que está al lado, ya dice la rama: el logo no agrega información para un lector de pantalla.

Revisar si `grupo.php` arma sus tarjetas de sección igual; si es así, el mismo reemplazo.

En el CSS, el cuadrado con logo va sobre blanco: el manual prohíbe aplicar una marca oficial sobre un fondo que le baje el contraste, y además el logo ya trae el color de la rama.

```css
.rama-icono.con-logo {
  background: var(--blanco);
  border-color: var(--rama-oscuro);
  padding: 8px;
}

.rama-icono img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
```

**Criterio:** las cuatro ramas muestran su logo oficial sobre blanco en las tarjetas de sección; una rama desconocida sigue mostrando el ícono de Bootstrap.

### T5 — Íconos de concepto (3C)

**✅ Hecha.** (El "Eventos" de `grupo.php` queda con Bootstrap: es un `boton-primario` de color y el ícono Orquídea no se leería encima.)

Los 18 archivos ya están en `views/assets/images/Iconos/` (ver "Archivos que se preparan a mano").

Tres métodos nuevos en `src/Helper/Vista.php`:

```php
    /**
     * URL de un ícono del sistema iconográfico oficial, por su slug
     * (ej. 'banderin'). Son los pictogramas lineales de Scouts de Argentina en
     * Orquídea Oscura, la variante que llega a 5,5:1 sobre blanco.
     */
    public static function iconoOficial(string $slug): string
    {
        return url("views/assets/images/Iconos/$slug.png");
    }

    /**
     * Ícono oficial de cada área de desarrollo (las 4 de LyL, Scouts y
     * Caminantes, y las 6 de Rovers). Null si el área no tiene uno: la vista
     * cae en iconoArea(), que devuelve el ícono de Bootstrap.
     */
    public static function iconoAreaOficial(int $idArea): ?string
    {
        $slug = match ($idArea) {
            1       => 'empatia',        // Salud y Bienestar
            2       => 'ods-17',         // Paz y Desarrollo
            3       => 'arbol',          // Ambiente
            4       => 'construcciones', // Habilidades para la Vida
            5       => 'montania',       // Corporalidad
            6       => 'ideas',          // Creatividad
            7       => 'linterna',       // Carácter
            8       => 'corazon',        // Afectividad
            9       => 'dialogo',        // Sociabilidad
            10      => 'espiritualidad', // Espiritualidad
            default => null,
        };

        return $slug === null ? null : self::iconoOficial($slug);
    }

    /**
     * Pictograma oficial de la rama, para donde el logo circular no entra: una
     * celda de tabla, un badge, una línea de texto. Null si la rama no tiene
     * uno: la vista cae en iconoRama().
     */
    public static function pictogramaRama(?int $idRama): ?string
    {
        $slug = match ($idRama) {
            1       => 'huella',              // Manada, el pictograma oficial de la rama
            2       => 'rosa-de-los-vientos', // Scouts: su bandera y sus insignias la forman
            3       => 'sol',                 // Caminantes: el Sol de Mayo de la Chakana
            4       => 'cayado',              // Rovers, el pictograma oficial de la rama
            default => null,
        };

        return $slug === null ? null : self::iconoOficial($slug);
    }
```

En el CSS, un ícono en línea que acompaña al texto:

```css
.icono-oficial {
  width: 1.25em;
  height: 1.25em;
  object-fit: contain;
  vertical-align: -0.25em;
}
```

Los lugares exactos donde se reemplaza, todos con `alt=""` porque el texto de al lado ya dice qué es:

**1. `gestionar_competencias.php`, botón de cada pestaña de área.** Reemplazar

```php
<i class="bi <?= e(Vista::iconoArea($idArea)) ?> me-1" aria-hidden="true"></i><?= e($area->contador->titulo) ?>
```

por

```php
<?php $iconoArea = Vista::iconoAreaOficial($idArea); ?>
<?php if ($iconoArea !== null): ?>
    <img class="icono-oficial me-1" src="<?= e($iconoArea) ?>" alt="">
<?php else: ?>
    <i class="bi <?= e(Vista::iconoArea($idArea)) ?> me-1" aria-hidden="true"></i>
<?php endif; ?>
<?= e($area->contador->titulo) ?>
```

**2. `progresion.php`, encabezado del acordeón de especialidades.** Mismo patrón, sobre

```php
<i class="bi <?= e(Vista::iconoArea((int) $area->id)) ?> me-2" aria-hidden="true"></i><?= e($area->titulo) ?>
```

usando `Vista::iconoAreaOficial((int) $area->id)` y la clase `icono-oficial me-2`.

**3. `perfil_protagonista.php`, lista de documentos.** Reemplazar

```php
<i class="bi <?= $esImagen ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' ?> me-1" aria-hidden="true"></i>
```

por

```php
<img class="icono-oficial me-1" src="<?= e(Vista::iconoOficial($esImagen ? 'png' : 'pdf')) ?>" alt="">
```

**4. `perfil_seccion.php`, links rápidos.** El de patrullas usa `bi-shield-shaded` y el de eventos `bi-megaphone`:

```php
<img class="icono-oficial me-1" src="<?= e(Vista::iconoOficial('banderin')) ?>" alt="">
<img class="icono-oficial me-1" src="<?= e(Vista::iconoOficial('campamento')) ?>" alt="">
```

Donde `patrullas.php`, `perfil_patrulla.php` o `eventos.php` usen esos mismos dos íconos de Bootstrap, el mismo reemplazo.

**5. El pictograma de rama.** Reemplazar **toda llamada a `Vista::iconoRama()` que no esté dentro de un `.rama-icono`** por el pictograma, con el mismo patrón de respaldo:

```php
<?php $pictograma = Vista::pictogramaRama($idRama); ?>
<?php if ($pictograma !== null): ?>
    <img class="icono-oficial me-1" src="<?= e($pictograma) ?>" alt="">
<?php else: ?>
    <i class="bi <?= e(Vista::iconoRama($idRama)) ?> me-1" aria-hidden="true"></i>
<?php endif; ?>
```

Los sitios conocidos son la lista "Mis roles" de `mi_cuenta.php` y la columna Sección de la nómina general en `protagonistas.php`, que hoy solo tiñe el nombre con `Vista::colorRama()`: ahí el pictograma va antes del nombre.

**El menú lateral no se toca.** `menu.php` imprime sus íconos desde el helper `$enlaceMenu`, que arma un `<i class="bi …">`: cambiarlo obligaría a rehacer la firma del helper para todos los links, y el menú no es donde se nota.

**Criterio:** las pestañas de área, el acordeón de especialidades, los documentos, los links de patrullas y eventos y los pictogramas de rama usan el sistema oficial; `views/assets/images/Iconos/` tiene 18 archivos y no 429.

### T6 — Insignias de progresión (4B)

**✅ Hecha.** (Los UPDATE quedaron en `educarama.sql`; sobre una base ya creada se corren a mano.)

**No hace falta cambiar el esquema:** `insignia.imagen` (varchar 255, nullable) ya existe y está en NULL en las 16 filas. Los archivos ya están renombrados (ver "Archivos que se preparan a mano").

**Qué se guarda:** solo el nombre del archivo, no la ruta. La carpeta es un detalle de presentación y vive en el helper.

Verificar que el Model `Insignia` tenga la propiedad `imagen` y que `desdeFila()` la mapee; si falta, agregarla.

**Datos.** Las once insignias que mapean una a una con su etapa. Va donde se cargan hoy las filas de `insignia` (tabla estática), y como UPDATE sobre la base ya creada:

```sql
UPDATE insignia SET imagen = 'lobatos-1-pata-tierna.png' WHERE id_etapa = 1;
UPDATE insignia SET imagen = 'lobatos-2-saltador.png'    WHERE id_etapa = 2;
UPDATE insignia SET imagen = 'lobatos-3-rastreador.png'  WHERE id_etapa = 3;
UPDATE insignia SET imagen = 'lobatos-4-cazador.png'     WHERE id_etapa = 4;
UPDATE insignia SET imagen = 'scouts-1-pistas.png'       WHERE id_etapa = 5;
UPDATE insignia SET imagen = 'scouts-2-senda.png'        WHERE id_etapa = 6;
UPDATE insignia SET imagen = 'scouts-3-rumbo.png'        WHERE id_etapa = 7;
UPDATE insignia SET imagen = 'scouts-4-travesia.png'     WHERE id_etapa = 8;
UPDATE insignia SET imagen = 'rovers-1-encuentro.png'    WHERE id_etapa = 13;
UPDATE insignia SET imagen = 'rovers-2-compromiso.png'   WHERE id_etapa = 14;
UPDATE insignia SET imagen = 'rovers-3-proyeccion.png'   WHERE id_etapa = 15;
```

Las insignias 9 a 12 (Caminantes) y la 16 (Ciudadanía) quedan con `imagen` en NULL: las de Caminantes porque su archivo depende del elemento, y Ciudadanía porque no tiene archivo y el manual aclara que no se usa en el uniforme.

**Helpers** en `src/Helper/Vista.php` (hay que agregar `use Educarama\Model\Enum\Elemento;`):

```php
    /** URL de la imagen de una insignia, a partir del nombre de archivo guardado en la fila. */
    public static function imagenInsignia(?string $archivo): ?string
    {
        return $archivo === null
            ? null
            : url('views/assets/images/Insignias/Progresion/' . $archivo);
    }

    /**
     * Imagen de una insignia de etapa de Caminantes. Las cuatro etapas comparten
     * fila en `insignia`, pero la insignia que recibe el protagonista depende del
     * elemento que eligió al confirmar la etapa (etapa_lograda.elemento). Sin
     * elemento (etapa todavía no lograda) no hay imagen.
     */
    public static function imagenInsigniaCaminantes(?Elemento $elemento): ?string
    {
        return $elemento === null
            ? null
            : self::imagenInsignia('caminantes-' . $elemento->value . '.png');
    }
```

**Vista.** En `progresion.php`, antes del `foreach` de insignias, armar el mapa de elementos a partir de las etapas que ya trae la pantalla:

```php
// Elemento elegido en cada etapa lograda (solo Caminantes): decide la imagen de su insignia.
$elementoPorEtapa = [];
foreach ($pantalla->etapas as $e) {
    if ($e->lograda?->elemento !== null) {
        $elementoPorEtapa[(int) $e->etapa->id] = $e->lograda->elemento;
    }
}
```

Dentro del `foreach`, junto a `$idInsignia` y `$pendiente`:

```php
        $idEtapaInsignia = $i->insignia->idEtapa;
        $imagenInsignia = $i->insignia->imagen !== null
            ? Vista::imagenInsignia($i->insignia->imagen)
            : ($idEtapaInsignia !== null
                ? Vista::imagenInsigniaCaminantes($elementoPorEtapa[(int) $idEtapaInsignia] ?? null)
                : null);
```

Y el círculo del ícono pasa de

```php
<div class="insignia-icono<?= $i->otorgada() ? '' : ' pendiente' ?>" aria-hidden="true"><i class="bi bi-award"></i></div>
```

a

```php
<div class="insignia-icono<?= $i->otorgada() ? '' : ' pendiente' ?><?= $imagenInsignia !== null ? ' con-imagen' : '' ?>" aria-hidden="true">
    <?php if ($imagenInsignia !== null): ?>
        <img src="<?= e($imagenInsignia) ?>" alt="">
    <?php else: ?>
        <i class="bi bi-award"></i>
    <?php endif; ?>
</div>
```

**CSS:**

```css
/* Con imagen, el círculo es solo el marco: la insignia va sobre blanco y sin
   filtros. Que esté entregada o no lo dice el badge, no la imagen. */
.insignia-icono.con-imagen {
  background: var(--blanco);
  border-color: var(--rama-oscuro);
  padding: 3px;
}

.insignia-icono img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
```

**Criterio:** Lobatos, Scouts y Rovers muestran la imagen de cada insignia; Caminantes la muestra en las etapas ya logradas y el ícono genérico en las que no; Ciudadanía muestra el ícono; `datos_ejemplo.sql` sigue importando sin errores sobre una base nueva.

### T7 — Marca y marco legal (5B)

**Bug encontrado:** `cabecera.php` sirve el logo desde `views/assets/images/Logo1.png`, pero el archivo está en `views/assets/images/Logos/Logos_Educarama/`. Hoy esa imagen no carga (pasa desapercibido porque tiene `alt=""`).

En `views/includes/cabecera.php`:

- [x] Corregir la ruta y pasar a la versión 4 del logo:
  ```php
  <img class="marca-logo rounded-circle" src="<?= e(url('views/assets/images/Logos/Logos_Educarama/LogoBaseV4.png')) ?>" alt="">
  ```
- [x] Agregar el favicon en el `<head>`, con el mismo logo:
  ```php
  <link rel="icon" type="image/png" href="<?= e(url('views/assets/images/Logos/Logos_Educarama/LogoBaseV4.png')) ?>">
  ```

En `views/includes/pie.php`, el pie dice hoy "Plataforma Educarama - Scouts de Argentina", que se lee como si fuera un producto oficial de la asociación. Pasa a:

```php
        <footer class="footer-inst">
            <p class="m-0 texto-blanco small">
                © <?= date('Y') ?> Educarama · Proyecto académico sin vinculación oficial con Scouts de Argentina Asociación Civil.<br>
                El programa educativo y los nombres de ramas, etapas y competencias pertenecen a
                <a href="https://scouts.org.ar" target="_blank" rel="noopener">Scouts de Argentina Asociación Civil</a>.
            </p>
        </footer>
```

En el CSS, para que el link del pie se lea sobre el degradado violeta:

```css
.footer-inst a {
  color: var(--blanco);
}
```

**Criterio:** ninguna marca oficial aparece como identidad del producto; el pie no sugiere respaldo institucional; el favicon es el logo de Educarama.

## Fuera de alcance

Zócalo de redes sociales · insignias de indumentaria y uniforme · iconografía religiosa y de áreas nacionales · Ubuntu Bold Condensed (no existe en la familia libre) · Insignia de Ciudadanía como insignia de progresión · los íconos del menú lateral.

## Pendiente de revisar

- ✅ Borrados. Del bloque `HOME DASHBOARD` del CSS, `.home-section-title` sí se usa (en `progresion.php`) y `.encabezado-grupo` también. Los que parecen huérfanos son `.home-stats`, `.home-stat-card`, `.home-table`, `.home-action-btn`, `.home-alerts`, `.home-grid`, `.home-card`, `.birthday-list` y `.movements-list`. Si una búsqueda lo confirma, se borran.
- ✅ Borrado. `.punto-rama` quedó sin uso después de reestructurar el drawer: el menú ya no lista secciones. Confirmar y borrar.
- ✅ Sin uso en el código; los archivos los borra Elías a mano. `Logo1.png` y `Logo1.jpg` quedan sin uso una vez que el header pase a `LogoBaseV4.png`.
- También sin uso, fuera de la lista original: `.home-container`, `.home-actions` y `.home-welcome`. Quedan hasta decidir.
