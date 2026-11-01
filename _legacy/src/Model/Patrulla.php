<?php

class Patrulla {
    public function __construct(
        public int $id,
        public ?string $nombre,
        public ?int $id_rama
    ) {}
}

?>