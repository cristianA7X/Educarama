<?php

class Insignia {
    public function __construct(
        public int $id,
        public ?string $nombre,
        public int $id_categoria_rama
    ) {}
}

?>