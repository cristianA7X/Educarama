<?php
// src/Service/Validacion.php

declare(strict_types=1);

namespace Educarama\Service;

use Educarama\Model\Persona;
use Educarama\Service\Excepcion\ReglaNegocioException;

/**
 * Validaciones de datos que comparten varios Services (fechas, personas, emails).
 * Son funciones puras: no consultan la base. Las que necesitan la base (por
 * ejemplo, que el DNI no esté repetido) las hace cada Service.
 *
 * Todas lanzan ReglaNegocioException con un mensaje para el usuario.
 */
final class Validacion
{
    // Solo funciones estáticas: no se crean objetos de esta clase.
    private function __construct() {}

    /**
     * Controla que $fecha sea una fecha real en formato 'AAAA-MM-DD' (el de
     * los Models) y la devuelve. "2026-02-30" no pasa.
     *
     * @param string $campo Cómo se llama el dato en el mensaje de error
     */
    public static function fecha(string $fecha, string $campo = 'La fecha'): string
    {
        $leida = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if ($leida === false || $leida->format('Y-m-d') !== $fecha) {
            throw new ReglaNegocioException("$campo no es válida (formato AAAA-MM-DD).");
        }

        return $fecha;
    }

    /**
     * Datos obligatorios de una persona: DNI (entero positivo), nombre y
     * apellido. La fecha de nacimiento es opcional, pero si viene tiene que ser real.
     * Limpia los espacios de nombre y apellido (modifica el objeto).
     */
    public static function persona(Persona $persona): void
    {
        $persona->nombre = trim(self::utf8($persona->nombre, 'El nombre'));
        $persona->apellido = trim(self::utf8($persona->apellido, 'El apellido'));

        if ($persona->nombre === '') {
            throw new ReglaNegocioException('El nombre es obligatorio.');
        }
        if ($persona->apellido === '') {
            throw new ReglaNegocioException('El apellido es obligatorio.');
        }
        if ($persona->dni <= 0) {
            throw new ReglaNegocioException('El DNI tiene que ser un número positivo.');
        }
        if ($persona->fechaNacimiento !== null) {
            self::fecha($persona->fechaNacimiento, 'La fecha de nacimiento');
        }
    }

    /**
     * Un texto opcional: sin espacios de más, y null si queda vacío.
     * Corta si supera el largo de su columna en la base.
     */
    public static function textoOpcional(?string $texto, int $largoMaximo, string $campo): ?string
    {
        $texto = $texto === null ? '' : trim(self::utf8($texto, $campo));

        if (mb_strlen($texto) > $largoMaximo) {
            throw new ReglaNegocioException("$campo puede tener hasta $largoMaximo caracteres.");
        }

        return $texto === '' ? null : $texto;
    }

    /**
     * Corta si el texto no es UTF-8 válido. Los formularios del navegador
     * mandan UTF-8; si llega otra codificación, MySQL guardaría "?" en lugar de
     * los caracteres raros sin avisar, así que es mejor rechazarlo.
     */
    private static function utf8(string $texto, string $campo): string
    {
        if (!mb_check_encoding($texto, 'UTF-8')) {
            throw new ReglaNegocioException("$campo tiene caracteres no válidos.");
        }

        return $texto;
    }

    /** Un email opcional: null si viene vacío; si viene, tiene que ser un email válido. */
    public static function emailOpcional(?string $email): ?string
    {
        $email = self::textoOpcional($email, 150, 'El email');

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ReglaNegocioException('El email no es válido.');
        }

        return $email;
    }
}
