<?php

namespace App\Models;

use MF\Model\Model;

class Recurso extends Model
{
    private $id;
    private $nome;
    private $tipo;
    private $quantidade;
    private $custo_unitario;
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
                tipo,
                quantidade,
                custo_unitario,
                estado,
                criado_em
            FROM recursos
            ORDER BY id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function criar()
    {
        $query = "
            INSERT INTO recursos
            (nome, tipo, quantidade, custo_unitario, estado)
            VALUES
            (:nome, :tipo, :quantidade, :custo_unitario, :estado)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':tipo', $this->__get('tipo'));
        $stmt->bindValue(':quantidade', $this->__get('quantidade'));
        $stmt->bindValue(':custo_unitario', $this->__get('custo_unitario'));
        $stmt->bindValue(':estado', $this->__get('estado'));

        return $stmt->execute();
    }

    public function editar()
    {
        $query = "
            UPDATE recursos
            SET
                nome = :nome,
                tipo = :tipo,
                quantidade = :quantidade,
                custo_unitario = :custo_unitario,
                estado = :estado
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':nome', $this->__get('nome'));
        $stmt->bindValue(':tipo', $this->__get('tipo'));
        $stmt->bindValue(':quantidade', $this->__get('quantidade'));
        $stmt->bindValue(':custo_unitario', $this->__get('custo_unitario'));
        $stmt->bindValue(':estado', $this->__get('estado'));

        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM recursos WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }

    public function atualizarQuantidade($id, $quantidade)
    {
        $query = "
            UPDATE recursos
            SET quantidade = :quantidade
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':quantidade', $quantidade);

        return $stmt->execute();
    }
}