<?

class Cumple {
    public function __construct(
        public int $id_protagonista,
        public int $id_accion,
        public ?bool $accion_cumplida
    ) {}
}

?>