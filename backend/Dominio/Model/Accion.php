<?php

class Accion {
    public function __construct(
        public int $id,
        public ?string $descripcion,
        public int $id_competencia,
        public int $codigo_competencia,
        public int $id_tipo_accion
    ) {}
}