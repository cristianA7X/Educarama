<?php

class Documento {
    public function __construct(
        public int $id,
        public ?string $tipo_documento,
        public ?string $descripcion,
        public int $id_protagonista
    ) {}
}