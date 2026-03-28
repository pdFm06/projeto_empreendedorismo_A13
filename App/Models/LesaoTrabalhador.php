<?php

namespace App\Models;

use MF\Model\Model;

class LesaoTrabalhador extends Model
{
    private $id;
    private $trabalhador_id;
    private $utilizador_id;
    private $titulo;
    private $descricao;
    private $data_lesao;
    private $data_regresso_prevista;
    private $data_regresso_real;
    private $estado;
    private $observacoes;

    public function __get($atributo)
    {
        return $this->$atributo;
    }

    public function __set($atributo, $valor)
    {
        $this->$atributo = $valor;
        return $this;
    }

    public function listarPorTrabalhador($trabalhadorId, $utilizadorId)
    {
        $query = "
            SELECT *
            FROM lesoes_trabalhadores
            WHERE trabalhador_id = :trabalhador_id
              AND utilizador_id = :utilizador_id
            ORDER BY data_lesao DESC, id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':trabalhador_id', $trabalhadorId);
        $stmt->bindValue(':utilizador_id', $utilizadorId);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obterPorIdEUtilizador($id, $utilizadorId)
    {
        $query = "
            SELECT *
            FROM lesoes_trabalhadores
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
            INSERT INTO lesoes_trabalhadores
            (trabalhador_id, utilizador_id, titulo, descricao, data_lesao, data_regresso_prevista, data_regresso_real, estado, observacoes)
            VALUES
            (:trabalhador_id, :utilizador_id, :titulo, :descricao, :data_lesao, :data_regresso_prevista, :data_regresso_real, :estado, :observacoes)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':trabalhador_id', $this->__get('trabalhador_id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));
        $stmt->bindValue(':titulo', $this->__get('titulo'));
        $stmt->bindValue(':descricao', $this->__get('descricao'));
        $stmt->bindValue(':data_lesao', $this->__get('data_lesao'));
        $stmt->bindValue(':data_regresso_prevista', $this->__get('data_regresso_prevista') ?: null);
        $stmt->bindValue(':data_regresso_real', $this->__get('data_regresso_real') ?: null);
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':observacoes', $this->__get('observacoes'));

        return $stmt->execute();
    }

    public function editar()
    {
        $query = "
            UPDATE lesoes_trabalhadores
            SET
                titulo = :titulo,
                descricao = :descricao,
                data_lesao = :data_lesao,
                data_regresso_prevista = :data_regresso_prevista,
                data_regresso_real = :data_regresso_real,
                estado = :estado,
                observacoes = :observacoes
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':titulo', $this->__get('titulo'));
        $stmt->bindValue(':descricao', $this->__get('descricao'));
        $stmt->bindValue(':data_lesao', $this->__get('data_lesao'));
        $stmt->bindValue(':data_regresso_prevista', $this->__get('data_regresso_prevista') ?: null);
        $stmt->bindValue(':data_regresso_real', $this->__get('data_regresso_real') ?: null);
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':observacoes', $this->__get('observacoes'));
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function eliminar($id, $utilizadorId)
    {
        $query = "
            DELETE FROM lesoes_trabalhadores
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':utilizador_id', $utilizadorId);

        return $stmt->execute();
    }
}