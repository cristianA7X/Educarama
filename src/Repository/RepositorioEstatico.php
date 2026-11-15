<?php // src/Repository/RepositorioEstatico.php
declare(strict_types=1);

namespace Educarama\Repository;

abstract class RepositorioEstatico extends RepositorioBase
{
    private static array $cache = [];

    protected function consultar(string $sql, array $params = []): array
    {
        $clave = $sql . '|' . json_encode($params);
        return self::$cache[$clave] ??= parent::consultar($sql, $params);
    }
}