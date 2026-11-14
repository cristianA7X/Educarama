<?php

class Competencia {
    public function __construct(
        public int $id,
        public int $codigo,
        public ?string $descripcion,
        public int $id_categoria_rama,
        public int $id_sendero
    ) {}
}

?>