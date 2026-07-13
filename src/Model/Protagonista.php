<?php

class Protagonista {
    public function __construct(
        public int $id,
        public ?bool $promesa,
        public int $id_persona,
        public int $id_rama,
        public ?int $id_patrulla,
        public ?int $id_usuario
    ) {}
}

?>