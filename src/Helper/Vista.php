<?php
// src/Helper/Vista.php

declare(strict_types=1);

namespace Educarama\Helper;

use Educarama\Model\Enum\Elemento;
use Educarama\Model\Enum\EstadoProgresion;
use Educarama\Model\Enum\EstadoSolicitud;
use Educarama\Model\Enum\Prioridad;

/**
 * Pequeñas ayudas para las páginas de views/pages/. Sin reglas de negocio:
 * solo traducen datos a lo que se imprime.
 */
final class Vista
{
    private function __construct() {}

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

    /**
     * URL de la imagen de una insignia, a partir del nombre de archivo guardado
     * en la fila (insignia.imagen). La carpeta es un detalle de presentación:
     * vive acá, no en la base.
     */
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

    /** Ícono de Bootstrap Icons de cada rama: último respaldo si no hay logo ni pictograma. */
    public static function iconoRama(?int $idRama): string
    {
        return match ($idRama) {
            1       => 'bi-star-fill',
            2       => 'bi-compass',
            3       => 'bi-signpost-split',
            4       => 'bi-globe-americas',
            default => 'bi-flag',
        };
    }

    /** Ícono de cada área (las 4 de LyL, Scouts y Caminantes, y las 6 de Rovers), para las pestañas. */
    public static function iconoArea(int $idArea): string
    {
        return match ($idArea) {
            1       => 'bi-heart-pulse',   // Salud y Bienestar
            2       => 'bi-people',        // Paz y Desarrollo
            3       => 'bi-tree',          // Ambiente
            4       => 'bi-lightbulb',     // Habilidades para la Vida
            5       => 'bi-person-arms-up',// Corporalidad
            6       => 'bi-palette',       // Creatividad
            7       => 'bi-shield-check',  // Carácter
            8       => 'bi-heart',         // Afectividad
            9       => 'bi-chat-dots',     // Sociabilidad
            10      => 'bi-sun',           // Espiritualidad
            default => 'bi-award',
        };
    }

    /** Etiqueta de un estado de progresión; null = pendiente (sin fila). */
    public static function estado(?EstadoProgresion $estado): string
    {
        return $estado?->etiqueta() ?? 'Pendiente';
    }

    /** Color de la etiqueta de un estado de progresión: pendiente gris, en proceso amarillo, desarrollada verde. */
    public static function colorEstadoProgresion(?EstadoProgresion $estado): string
    {
        return match ($estado) {
            null                           => 'secondary',
            EstadoProgresion::EnProceso    => 'warning',
            EstadoProgresion::Desarrollada => 'success',
        };
    }

    /** Edad en años cumplidos a hoy, o null si no hay fecha de nacimiento. */
    public static function edad(?string $fechaNacimiento): ?int
    {
        if ($fechaNacimiento === null) {
            return null;
        }
        $nacimiento = \DateTimeImmutable::createFromFormat('!Y-m-d', $fechaNacimiento);

        return $nacimiento === false ? null : $nacimiento->diff(new \DateTimeImmutable('today'))->y;
    }

    /** 1536 → '1,5 KB'; 2621440 → '2,5 MB'. */
    public static function tamanio(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $kb = $bytes / 1024;

        return $kb < 1024
            ? number_format($kb, 1, ',', '.') . ' KB'
            : number_format($kb / 1024, 1, ',', '.') . ' MB';
    }

    /** Color (de Bootstrap) de la etiqueta de la prioridad de un evento: alta roja, media amarilla, baja gris. */
    public static function colorPrioridad(Prioridad $prioridad): string
    {
        return match ($prioridad) {
            Prioridad::Alta  => 'danger',
            Prioridad::Media => 'warning',
            Prioridad::Baja  => 'secondary',
        };
    }

    /** Color de la etiqueta del estado de una solicitud de alta. */
    public static function colorEstado(EstadoSolicitud $estado): string
    {
        return match ($estado) {
            EstadoSolicitud::Pendiente => 'warning',
            EstadoSolicitud::Aprobada  => 'success',
            EstadoSolicitud::Rechazada => 'danger',
        };
    }

    /** '14/11/2026', o '10/01/2027 al 17/01/2027' si el evento tiene fecha de fin. */
    public static function periodo(string $inicio, ?string $fin): string
    {
        return $fin === null || $fin === $inicio
            ? self::fecha($inicio)
            : self::fecha($inicio) . ' al ' . self::fecha($fin);
    }

    /** '2026-10-28 20:15:00' → '28/10/2026' (la fecha de un DATETIME). */
    public static function fechaDeFechaHora(?string $fechaHora): string
    {
        return self::fecha($fechaHora === null ? null : substr($fechaHora, 0, 10));
    }

    /**
     * Prefijo de sección según la rama, para concatenar con el nombre particular.
     * Ej: prefijoSeccion(1) → 'Manada', prefijoSeccion(3) → 'Comunidad Caminante'.
     */
    public static function prefijoSeccion(int $idRama): string
    {
        return match ($idRama) {
            1       => 'Manada',
            2       => 'Unidad',
            3       => 'Comunidad Caminante',
            4       => 'Comunidad Rover',
            default => '',
        };
    }

    /**
     * Nombre completo de la sección: prefijo de la rama + nombre particular.
     * Ej: nombreCompletoSeccion('Luna Llena', 1) → 'Manada Luna Llena'.
     */
    public static function nombreCompletoSeccion(string $nombre, int $idRama): string
    {
        $prefijo = self::prefijoSeccion($idRama);

        return $prefijo !== '' ? "$prefijo $nombre" : $nombre;
    }

    /**
     * Nombre completo del grupo: "Grupo Scout" + nombre particular.
     * Ej: nombreCompletoGrupo('Los Aromos') → 'Grupo Scout Los Aromos'.
     */
    public static function nombreCompletoGrupo(string $nombre): string
    {
        return "Grupo Scout $nombre";
    }

    /** '2026-11-14' → '14/11/2026'. Si no es una fecha 'AAAA-MM-DD', la devuelve igual. */
    public static function fecha(?string $fecha): string
    {
        if ($fecha === null || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m) !== 1) {
            return $fecha ?? '';
        }

        return "$m[3]/$m[2]/$m[1]";
    }

    /**
     * Campo de fecha con tres listas (día, mes y año) en vez de <input type="date">.
     * Para fechas lejanas, como la de nacimiento: el calendario del navegador
     * arranca en hoy y trae un botón "Hoy" que no se puede quitar y se confunde
     * con el de enviar.
     *
     * Las listas no tienen name: lo que viaja es el campo oculto $nombre, con la
     * fecha 'AAAA-MM-DD' (o vacío), que arma public/js/educarama.js. Así el
     * servidor recibe lo mismo que con un input de fecha. El JS también marca
     * como inválida una fecha incompleta, inexistente (31 de febrero) o futura.
     *
     * @param string      $id       Id de la lista del día: el <label for> apunta acá.
     * @param ?string     $valor    Fecha guardada 'AAAA-MM-DD', o null.
     * @param int         $anios    Cuántos años hacia atrás ofrece la lista de años.
     */
    public static function campoFecha(string $nombre, string $id, ?string $valor, int $anios = 100): string
    {
        $dia = $mes = $anio = 0;
        if ($valor !== null && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m) === 1) {
            [$anio, $mes, $dia] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        }

        // Abreviados: con el nombre completo, las tres listas no entran en un celular.
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $opcion = static fn (int $v, string $texto, int $elegido): string =>
            '<option value="' . $v . '"' . ($v === $elegido ? ' selected' : '') . '>' . e($texto) . '</option>';

        $html = '<div class="campo-fecha" data-campo-fecha data-max="' . e(date('Y-m-d')) . '">'
            . '<input type="hidden" name="' . e($nombre) . '" value="' . e($valor ?? '') . '">';

        $html .= '<select class="form-select" id="' . e($id) . '" data-parte="dia" aria-label="Día"><option value="">Día</option>';
        for ($d = 1; $d <= 31; $d++) {
            $html .= $opcion($d, (string) $d, $dia);
        }

        $html .= '</select><select class="form-select" data-parte="mes" aria-label="Mes"><option value="">Mes</option>';
        foreach ($meses as $i => $nombreMes) {
            $html .= $opcion($i + 1, $nombreMes, $mes);
        }

        // Años de este hacia atrás: las fechas de nacimiento están lejos de hoy.
        $html .= '</select><select class="form-select" data-parte="anio" aria-label="Año"><option value="">Año</option>';
        $actual = (int) date('Y');
        for ($a = $actual; $a >= $actual - $anios; $a--) {
            $html .= $opcion($a, (string) $a, $anio);
        }

        return $html . '</select></div>';
    }
}
