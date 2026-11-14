<?php

class Rama {
    public function __construct(
        public int $id,
        public ?string $nombre,
        public int $id_grupo,
        public int $id_rama
    ) {}
}

?>