<?php

class Desarrolla {
    public function __construct(
        public int $id_protagonista,
        public int $id_competencia,
        public int $codigo_competencia,
        public ?bool $competencia_desarrollada
    ) {}
}

?>