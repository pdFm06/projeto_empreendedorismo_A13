<?php

namespace App\Models;

use MF\Model\Model;

class Equipa extends Model
{
    private $id;
    private $nome;
    private $especialidade;
    private $lider_id;
    private $utilizador_id;

    public function __get($atributo)
    {
        return $this->$atributo;
    }

    public function __set($atributo, $valor)
    {
        $this->$atributo = $valor;
        return $this;
    }

    public function listarPorUtilizador($utilizadorId)
    {
        $query = "
            SELECT
                e.id,
                e.nome,
                e.especialidade,
                e.lider_id,
                e.utilizador_id,
                t.nome AS lider_nome,
                t.funcao AS lider_funcao,
                e.criado_em
            FROM equipas e
            LEFT JOIN trabalhadores t ON t.id = e.lider_id
            WHERE e.utilizador_id = :utilizador_id
            ORDER BY e.id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':utilizador_id', $utilizadorId);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obterPorIdEUtilizador($id, $utilizadorId)
    {
        $query = "
            SELECT *
            FROM equipas
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':utilizador_id', $utilizadorId);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function criar()
    {
        $query = "
            INSERT INTO equipas
            (nome, especialidade, lider_id, utilizador_id)
            VALUES
            (:nome, :especialidade, :lider_id, :utilizador_id)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':especialidade', $this->__get('especialidade'));
        $stmt->bindValue(':lider_id', $this->__get('lider_id') ?: null);
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));
        $stmt->execute();

        return $this->db->lastInsertId();
    }

    public function editar()
    {
        $query = "
            UPDATE equipas
            SET
                nome = :nome,
                especialidade = :especialidade,
                lider_id = :lider_id
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':especialidade', $this->__get('especialidade'));
        $stmt->bindValue(':lider_id', $this->__get('lider_id') ?: null);
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function eliminar($id, $utilizadorId)
    {
        $query = "
            DELETE FROM equipas
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':utilizador_id', $utilizadorId);

        return $stmt->execute();
    }

    public function listarMembros($equipaId)
    {
        $query = "
            SELECT
                et.id,
                et.equipa_id,
                et.trabalhador_id,
                et.data_entrada,
                t.nome,
                t.email,
                t.telefone,
                t.funcao,
                t.estado
            FROM equipa_trabalhador et
            INNER JOIN trabalhadores t ON t.id = et.trabalhador_id
            WHERE et.equipa_id = :equipa_id
            ORDER BY t.nome ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':equipa_id', $equipaId);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function adicionarMembro($equipaId, $trabalhadorId)
    {
        $query = "
            INSERT INTO equipa_trabalhador
            (equipa_id, trabalhador_id, data_entrada)
            VALUES
            (:equipa_id, :trabalhador_id, CURDATE())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':equipa_id', $equipaId);
        $stmt->bindValue(':trabalhador_id', $trabalhadorId);

        return $stmt->execute();
    }

    public function removerMembro($equipaId, $trabalhadorId)
    {
        $query = "
            DELETE FROM equipa_trabalhador
            WHERE equipa_id = :equipa_id
              AND trabalhador_id = :trabalhador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':equipa_id', $equipaId);
        $stmt->bindValue(':trabalhador_id', $trabalhadorId);

        return $stmt->execute();
    }

    public function listarMembrosIndisponiveisNoPeriodo($equipaId, $utilizadorId, $dataInicio, $dataFim)
    {
    $query = "
        SELECT DISTINCT
            t.id,
            t.nome,
            t.funcao,
            t.estado,
            lt.estado AS lesao_estado,
            lt.data_lesao,
            lt.data_regresso_prevista,
            lt.data_regresso_real
        FROM equipa_trabalhador et
        INNER JOIN trabalhadores t
            ON t.id = et.trabalhador_id
        LEFT JOIN lesoes_trabalhadores lt
            ON lt.trabalhador_id = t.id
           AND lt.utilizador_id = :utilizador_id
        WHERE et.equipa_id = :equipa_id
          AND t.utilizador_id = :utilizador_id
          AND (
                t.estado = 'inativo'
                OR
                (
                    lt.id IS NOT NULL
                    AND lt.estado IN ('ativa', 'em_recuperacao')
                    AND (
                        :data_inicio_1 IS NULL
                        OR :data_fim_1 IS NULL
                        OR (
                            lt.data_lesao <= :data_fim_2
                            AND COALESCE(lt.data_regresso_real, lt.data_regresso_prevista, '9999-12-31') >= :data_inicio_2
                        )
                    )
                )
              )
        ORDER BY t.nome ASC
    ";

    $stmt = $this->db->prepare($query);
    $stmt->bindValue(':equipa_id', $equipaId);
    $stmt->bindValue(':utilizador_id', $utilizadorId);
    $stmt->bindValue(':data_inicio_1', $dataInicio ?: null);
    $stmt->bindValue(':data_fim_1', $dataFim ?: null);
    $stmt->bindValue(':data_fim_2', $dataFim ?: null);
    $stmt->bindValue(':data_inicio_2', $dataInicio ?: null);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}