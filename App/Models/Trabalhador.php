<?php

namespace App\Models;

use MF\Model\Model;

class Trabalhador extends Model
{
    private $id;
    private $nome;
    private $email;
    private $telefone;
    private $funcao;
    private $salario_dia;
    private $estado;
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
                id,
                nome,
                email,
                telefone,
                funcao,
                salario_dia,
                estado,
                utilizador_id,
                criado_em
            FROM trabalhadores
            WHERE utilizador_id = :utilizador_id
            ORDER BY id DESC
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
            FROM trabalhadores
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
            INSERT INTO trabalhadores
            (nome, email, telefone, funcao, salario_dia, estado, utilizador_id)
            VALUES
            (:nome, :email, :telefone, :funcao, :salario_dia, :estado, :utilizador_id)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->bindValue(':telefone', $this->__get('telefone'));
        $stmt->bindValue(':funcao', $this->__get('funcao'));
        $stmt->bindValue(':salario_dia', $this->__get('salario_dia'));
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function editar()
    {
        $query = "
            UPDATE trabalhadores
            SET
                nome = :nome,
                email = :email,
                telefone = :telefone,
                funcao = :funcao,
                salario_dia = :salario_dia,
                estado = :estado
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->bindValue(':telefone', $this->__get('telefone'));
        $stmt->bindValue(':funcao', $this->__get('funcao'));
        $stmt->bindValue(':salario_dia', $this->__get('salario_dia'));
        $stmt->bindValue(':estado', $this->__get('estado'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function eliminar($id, $utilizadorId)
    {
        $query = "
            DELETE FROM trabalhadores
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':utilizador_id', $utilizadorId);

        return $stmt->execute();
    }

    public function obterPorEmailEUtilizador($email, $utilizadorId)
    {
        $query = "
            SELECT *
            FROM trabalhadores
            WHERE email = :email
            AND utilizador_id = :utilizador_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':utilizador_id', $utilizadorId);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function obterPorEmailEUtilizadorExcetoId($email, $utilizadorId, $id)
    {
        $query = "
            SELECT *
            FROM trabalhadores
            WHERE email = :email
            AND utilizador_id = :utilizador_id
            AND id <> :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':utilizador_id', $utilizadorId);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}