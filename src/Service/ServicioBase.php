<?php // src/Service/ServicioBase.php
declare(strict_types=1);

namespace Educarama\Service;

use PDO;
use Throwable;

/**
 * Base de todos los Services.
 * Un Service aplica las reglas de negocio y usa los repositorios para leer y guardar.
 * No toca $_SESSION ni imprime HTML: recibe datos (incluido el id del usuario
 * actual) y devuelve Models, o lanza una excepción de Educarama\Service\Excepcion.
 * La única excepción es UsuarioService, que maneja la sesión (login y logout).
 */
abstract class ServicioBase
{
    public function __construct(
        protected readonly PDO $conexion
    ) {}

    /**
     * Ejecuta $operacion dentro de una transacción: si termina bien hace commit,
     * y si lanza cualquier excepción hace rollback y la vuelve a lanzar.
     * Devuelve lo que devuelva $operacion.
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    protected function transaccion(callable $operacion): mixed
    {
        if ($this->conexion->inTransaction()) {
            return $operacion();
        }
        $this->conexion->beginTransaction();
        try {
            $resultado = $operacion();
            $this->conexion->commit();
            return $resultado;
        } catch (Throwable $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }
}