<?php

namespace App\Models;

use MF\Model\Model;

class Projeto extends Model
{
    private $id;
    private $nome;
    private $descricao;
    private $localizacao;
    private $data_inicio;
    private $data_fim_prevista;
    private $data_fim_real;
    private $estado;
    private $orcamento;
    private $gestor_id;
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
                p.id,
                p.nome,
                p.descricao,
                p.localizacao,
                p.data_inicio,
                p.data_fim_prevista,
                p.data_fim_real,
                p.estado,
                p.orcamento,
                p.gestor_id,
                p.utilizador_id,
                p.criado_em,
                u.email AS gestor_nome
            FROM projetos p
            INNER JOIN utilizadores u ON u.id = p.gestor_id
            WHERE p.utilizador_id = :utilizador_id
            ORDER BY p.id DESC
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
            FROM projetos 
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
            INSERT INTO projetos
            (nome, descricao, localizacao, data_inicio, data_fim_prevista, estado, orcamento, gestor_id, utilizador_id)
            VALUES
            (:nome, :descricao, :localizacao, :data_inicio, :data_fim_prevista, :estado, :orcamento, :gestor_id, :utilizador_id)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':descricao', $this->__get('descricao'));
        $stmt->bindValue(':localizacao', $this->__get('localizacao'));
        $stmt->bindValue(':data_inicio', $this->__get('data_inicio'));
        $stmt->bindValue(':data_fim_prevista', $this->__get('data_fim_prevista'));
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':orcamento', $this->__get('orcamento'));
        $stmt->bindValue(':gestor_id', $this->__get('gestor_id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function editar()
    {
        $query = "
            UPDATE projetos
            SET
                nome = :nome,
                descricao = :descricao,
                localizacao = :localizacao,
                data_inicio = :data_inicio,
                data_fim_prevista = :data_fim_prevista,
                estado = :estado,
                orcamento = :orcamento,
                gestor_id = :gestor_id
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':descricao', $this->__get('descricao'));
        $stmt->bindValue(':localizacao', $this->__get('localizacao'));
        $stmt->bindValue(':data_inicio', $this->__get('data_inicio'));
        $stmt->bindValue(':data_fim_prevista', $this->__get('data_fim_prevista'));
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':orcamento', $this->__get('orcamento'));
        $stmt->bindValue(':gestor_id', $this->__get('gestor_id'));
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function eliminar($id, $utilizadorId)
    {
        $query = "
            DELETE FROM projetos 
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':utilizador_id', $utilizadorId);

        return $stmt->execute();
    }

    public function listarEquipas($projetoId)
    {
        $query = "
            SELECT
                pe.id,
                pe.projeto_id,
                pe.equipa_id,
                e.nome,
                e.especialidade,
                e.lider_id,
                t.nome AS lider_nome
            FROM projeto_equipa pe
            INNER JOIN equipas e ON e.id = pe.equipa_id
            LEFT JOIN trabalhadores t ON t.id = e.lider_id
            WHERE pe.projeto_id = :projeto_id
            ORDER BY e.nome ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function adicionarEquipa($projetoId, $equipaId)
    {
        $query = "
            INSERT INTO projeto_equipa
            (projeto_id, equipa_id)
            VALUES
            (:projeto_id, :equipa_id)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->bindValue(':equipa_id', $equipaId);

        return $stmt->execute();
    }

    public function removerEquipa($projetoId, $equipaId)
    {
        $query = "
            DELETE FROM projeto_equipa
            WHERE projeto_id = :projeto_id
              AND equipa_id = :equipa_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->bindValue(':equipa_id', $equipaId);

        return $stmt->execute();
    }
}