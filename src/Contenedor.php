<?php
// src/Contenedor.php
declare(strict_types=1);

namespace Educarama;

class Contenedor
{
    private array $instancias = [];
    private array $fabricas = [];

    /**
     * Registra cómo crear una dependencia.
     * $fabrica recibe el contenedor como parámetro para resolver dependencias anidadas.
     */
    public function registrar(string $clase, callable $fabrica): void
    {
        $this->fabricas[$clase] = $fabrica;
    }

    /**
     * Obtiene una instancia (como un Singleton en el ciclo de vida de la petición).
     */
    public function obtener(string $clase)
    {
        if (isset($this->instancias[$clase])) {
            return $this->instancias[$clase];
        }

        if (!isset($this->fabricas[$clase])) {
            throw new \Exception("Dependencia no registrada: " . $clase);
        }

        $this->instancias[$clase] = ($this->fabricas[$clase])($this);
        return $this->instancias[$clase];
    }
}