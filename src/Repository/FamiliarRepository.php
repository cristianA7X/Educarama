<?php // src/Repository/FamiliarRepository.php
declare(strict_types=1);

namespace Educarama\Repository;

use Educarama\Model\Familiar;
use Educarama\Repository\Vista\FichaFamiliar;
use PDO;

final class FamiliarRepository extends RepositorioBase
{
    private const SELECT_FICHA =
        'SELECT f.*,
                per.dni                       AS persona_dni,
                per.nombre                    AS persona_nombre,
                per.apellido                  AS persona_apellido,
                per.fecha_nacimiento          AS persona_fecha_nacimiento,
                ef.id_protagonista            AS ef_id_protagonista,
                ef.vinculo                    AS ef_vinculo,
                ef.es_contacto_de_emergencia  AS ef_es_contacto_de_emergencia
           FROM familiar f
           JOIN persona per    ON per.id = f.id_persona
           JOIN es_familia ef  ON ef.id_familiar = f.id';

    public function __construct(PDO $conexion)
    {
        parent::__construct($conexion);
    }

    public function buscarPorId(int $id): ?Familiar
    {
        return $this->buscarUno('SELECT * FROM familiar WHERE id = ?', [$id], Familiar::class);
    }

    public function buscarPorPersona(int $idPersona): ?Familiar
    {
        return $this->buscarUno('SELECT * FROM familiar WHERE id_persona = ?', [$idPersona], Familiar::class);
    }

    public function listarPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            'SELECT f.*
               FROM familiar f
               JOIN es_familia ef ON ef.id_familiar = f.id
              WHERE ef.id_protagonista = ?
              ORDER BY ef.es_contacto_de_emergencia DESC, f.id',
            [$idProtagonista],
            Familiar::class,
        );
    }

    public function listarFichasPorProtagonista(int $idProtagonista): array
    {
        return $this->listar(
            self::SELECT_FICHA . ' WHERE ef.id_protagonista = ?
              ORDER BY ef.es_contacto_de_emergencia DESC, per.apellido, per.nombre',
            [$idProtagonista],
            FichaFamiliar::class,
        );
    }

    public function buscarFicha(int $idProtagonista, int $idFamiliar): ?FichaFamiliar
    {
        return $this->buscarUno(
            self::SELECT_FICHA . ' WHERE ef.id_protagonista = ? AND f.id = ?',
            [$idProtagonista, $idFamiliar],
            FichaFamiliar::class,
        );
    }

    public function insertar(Familiar $familiar): int
    {
        $familiar->id = $this->insertarFila(
            'INSERT INTO familiar (id_persona, telefono_contacto, email_contacto)
             VALUES (:id_persona, :telefono_contacto, :email_contacto)',
            $this->parametros($familiar),
        );
        return $familiar->id;
    }

    public function actualizar(Familiar $familiar): void
    {
        $this->ejecutar(
            'UPDATE familiar
                SET id_persona = :id_persona, telefono_contacto = :telefono_contacto, email_contacto = :email_contacto
              WHERE id = :id',
            $this->parametros($familiar) + ['id' => $this->exigirId($familiar->id)],
        );
    }

    public function eliminar(int $id): void
    {
        $this->ejecutar('DELETE FROM familiar WHERE id = ?', [$id]);
    }

    private function parametros(Familiar $familiar): array
    {
        return [
            'id_persona'        => $familiar->idPersona,
            'telefono_contacto' => $familiar->telefonoContacto,
            'email_contacto'    => $familiar->emailContacto,
        ];
    }
}