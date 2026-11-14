<?

class EsFamilia {
    public function __construct(
        public int $id_protagonista,
        public int $id_familiar,
        public ?string $vinculo,
        public ?bool $es_contacto_de_emergencia
    ) {}
}

?>