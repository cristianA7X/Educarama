<?php
// src/Model/Usuario.php
// Esta clase representa un usuario en el sistema y contiene propiedades que corresponden a los campos de la tabla de usuarios en la base de datos.
class Usuario {
    public function __construct(
        public int $id,
        public ?string $nombre,
        public ?string $email,
        public ?string $contrasenia
    ) {}
}

?>