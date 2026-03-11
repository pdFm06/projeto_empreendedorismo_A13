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
                u.email AS gestor_nome
            FROM projetos p
            INNER JOIN utilizadores u ON u.id = p.gestor_id
            ORDER BY p.id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obterPorId($id)
    {
        $query = "
            SELECT * 
            FROM projetos 
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function criar()
    {
        $query = "
            INSERT INTO projetos
            (nome, descricao, localizacao, data_inicio, data_fim_prevista, estado, orcamento, gestor_id)
            VALUES
            (:nome, :descricao, :localizacao, :data_inicio, :data_fim_prevista, :estado, :orcamento, :gestor_id)
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

    return $stmt->execute();
}

    public function eliminar($id)
    {
        $query = "DELETE FROM projetos WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }
}