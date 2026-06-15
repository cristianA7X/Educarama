<?php

class AsignacionRol {
    public function __construct(
        public int $id,
        public int $id_usuario,
        public int $id_rol,
        public ?int $id_grupo,
        public ?int $id_rama
    ) {}
}

?>