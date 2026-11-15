<?php

class Persona {
    public function __construct(
        public int $id,
        public int $dni,
        public ?string $nombre,
        public ?string $apellido,
        public ?string $fecha_nacimiento
    ) {}
}

?>