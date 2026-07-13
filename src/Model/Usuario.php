<?php

class Usuario {
    public function __construct(
        public int $id,
        public ?string $nombre,
        public ?string $email,
        public ?string $contrasenia
    ) {}
}

?>