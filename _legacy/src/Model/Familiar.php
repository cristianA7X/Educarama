<?php

class Familiar {
    public function __construct(
        public int $id,
        public ?string $telefono_contacto,
        public ?string $email_contacto,
        public ?int $id_persona
    ) {}
}

?>