<?php

namespace App\Models;

use MF\Model\Model;

class Tarefa extends Model
{
    private $id;
    private $projeto_id;
    private $trabalhador_id;
    private $utilizador_id;
    private $titulo;
    private $descricao;
    private $estado;
    private $prioridade;
    private $data_limite;

    public function __get($atributo)
    {
        return $this->$atributo;
    }

    public function __set($atributo, $valor)
    {
        $this->$atributo = $valor;
        return $this;
    }

    public function listarPorProjeto($projetoId, $utilizadorId)
    {
        $query = "
            SELECT
                t.*,
                t.criado_em,
                tr.nome AS trabalhador_nome
            FROM tarefas t
            LEFT JOIN trabalhadores tr ON tr.id = t.trabalhador_id
            WHERE t.projeto_id = :projeto_id
              AND t.utilizador_id = :utilizador_id
            ORDER BY
                FIELD(t.estado, 'pendente', 'em_progresso', 'concluida'),
                FIELD(t.prioridade, 'alta', 'media', 'baixa'),
                t.id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->bindValue(':utilizador_id', $utilizadorId);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listarPorUtilizador($utilizadorId)
    {
        $query = "
            SELECT
                t.id,
                t.projeto_id,
                t.trabalhador_id,
                t.utilizador_id,
                t.titulo,
                t.descricao,
                t.estado,
                t.prioridade,
                t.data_limite,
                t.criado_em,
                tr.nome AS trabalhador_nome
            FROM tarefas t
            LEFT JOIN trabalhadores tr ON tr.id = t.trabalhador_id
            WHERE t.utilizador_id = :utilizador_id
            ORDER BY t.id DESC
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
            FROM tarefas
            WHERE id = :id
              AND utilizador_id = :utilizador_id
            LIMIT 1
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
            INSERT INTO tarefas
            (projeto_id, trabalhador_id, utilizador_id, titulo, descricao, estado, prioridade, data_limite)
            VALUES
            (:projeto_id, :trabalhador_id, :utilizador_id, :titulo, :descricao, :estado, :prioridade, :data_limite)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $this->__get('projeto_id'));
        $stmt->bindValue(':trabalhador_id', $this->__get('trabalhador_id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));
        $stmt->bindValue(':titulo', $this->__get('titulo'));
        $stmt->bindValue(':descricao', $this->__get('descricao'));
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':prioridade', $this->__get('prioridade'));
        $stmt->bindValue(':data_limite', $this->__get('data_limite'));

        return $stmt->execute();
    }

    public function editar()
    {
        $query = "
            UPDATE tarefas
            SET
                trabalhador_id = :trabalhador_id,
                titulo = :titulo,
                descricao = :descricao,
                estado = :estado,
                prioridade = :prioridade,
                data_limite = :data_limite
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':trabalhador_id', $this->__get('trabalhador_id'));
        $stmt->bindValue(':titulo', $this->__get('titulo'));
        $stmt->bindValue(':descricao', $this->__get('descricao'));
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':prioridade', $this->__get('prioridade'));
        $stmt->bindValue(':data_limite', $this->__get('data_limite'));
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function eliminar($id, $utilizadorId)
    {
        $query = "
            DELETE FROM tarefas
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':utilizador_id', $utilizadorId);

        return $stmt->execute();
    }
}