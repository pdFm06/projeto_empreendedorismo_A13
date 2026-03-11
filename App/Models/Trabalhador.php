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
                id,
                nome,
                email,
                telefone,
                funcao,
                salario_dia,
                estado,
                criado_em
            FROM trabalhadores
            ORDER BY id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obterPorId($id)
    {
        $query = "SELECT * FROM trabalhadores WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function criar()
    {
        $query = "
            INSERT INTO trabalhadores
            (nome, email, telefone, funcao, salario_dia, estado)
            VALUES
            (:nome, :email, :telefone, :funcao, :salario_dia, :estado)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->bindValue(':telefone', $this->__get('telefone'));
        $stmt->bindValue(':funcao', $this->__get('funcao'));
        $stmt->bindValue(':salario_dia', $this->__get('salario_dia'));
        $stmt->bindValue(':estado', $this->__get('estado'));

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
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->bindValue(':telefone', $this->__get('telefone'));
        $stmt->bindValue(':funcao', $this->__get('funcao'));
        $stmt->bindValue(':salario_dia', $this->__get('salario_dia'));
        $stmt->bindValue(':estado', $this->__get('estado'));

        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM trabalhadores WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }
}