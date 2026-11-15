<?php
// src/Controller/Controlador.php
declare(strict_types=1);

namespace Educarama\Controller;

use Educarama\Service\Excepcion\ReglaNegocioException;
use Educarama\Service\UsuarioService;
use Educarama\Service\Vista\UsuarioSesion;

abstract class Controlador
{
    public function __construct(
        protected readonly UsuarioService $usuarioService
    ) {}

    protected function usuario(): UsuarioSesion
    {
        return $this->usuarioService->exigirSesion();
    }

    protected static function texto(array $datos, string $clave): string
    {
        $valor = $datos[$clave] ?? '';
        return is_string($valor) ? $valor : '';
    }

    protected static function entero(array $datos, string $clave): int
    {
        return self::enteroOpcional($datos, $clave)
            ?? throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.');
    }

    protected static function enteroOpcional(array $datos, string $clave): ?int
    {
        $valor = $datos[$clave] ?? null;
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_int($valor)) {
            return $valor > 0 ? $valor : null;
        }
        if (is_string($valor) && ctype_digit($valor) && strlen($valor) <= 10 && (int) $valor > 0) {
            return (int) $valor;
        }
        throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.');
    }

    protected static function listaDeEnteros(array $datos, string $clave): array
    {
        $valores = $datos[$clave] ?? [];
        if (!is_array($valores)) {
            throw new ReglaNegocioException('Faltan datos o no son válidos. Volvé a cargar la página.');
        }

        return array_values(array_map(
            fn ($v) => self::entero(['v' => $v], 'v'),
            $valores,
        ));
    }

    protected static function dni(array $datos, string $clave = 'dni'): int
    {
        $texto = str_replace(['.', ' '], '', self::texto($datos, $clave));
        return ctype_digit($texto) && strlen($texto) <= 9 ? (int) $texto : 0;
    }

    protected static function fechaOpcional(array $datos, string $clave): ?string
    {
        $texto = trim(self::texto($datos, $clave));
        return $texto === '' ? null : $texto;
    }

    protected static function booleano(array $datos, string $clave): bool
    {
        $valor = $datos[$clave] ?? null;
        return in_array($valor, ['1', 'true', 'on', 1, true], true);
    }

    protected static function rutaInterna(string $ruta): ?string
    {
        return preg_match('~^views/pages/[a-z0-9_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$~', $ruta) === 1 ? $ruta : null;
    }
}