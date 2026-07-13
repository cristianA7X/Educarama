<?php

class Grupo {
    public function __construct(
        public int $id,
        public string $codigo,
        public ?string $nombre
    ) {}
}

?>