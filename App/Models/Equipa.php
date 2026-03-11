<?php

namespace App\Models;

use MF\Model\Model;

class Equipa extends Model
{
    private $id;
    private $nome;
    private $especialidade;
    private $lider_id;

    public function __get($atributo)
    {
        return $this->$atributo;
    }

    public function __set($atributo, $valor)
    {
        $this->$atributo = $valor;
        return $this;
    }

    public function listar()
    {
        $query = "
            SELECT
                e.id,
                e.nome,
                e.especialidade,
                e.lider_id,
                t.nome AS lider_nome,
                t.funcao AS lider_funcao,
                e.criado_em
            FROM equipas e
            LEFT JOIN trabalhadores t ON t.id = e.lider_id
            ORDER BY e.id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function criar()
    {
        $query = "
            INSERT INTO equipas
            (nome, especialidade, lider_id)
            VALUES
            (:nome, :especialidade, :lider_id)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':especialidade', $this->__get('especialidade'));
        $stmt->bindValue(':lider_id', $this->__get('lider_id') ?: null);
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
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':especialidade', $this->__get('especialidade'));
        $stmt->bindValue(':lider_id', $this->__get('lider_id') ?: null);

        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM equipas WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

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
}