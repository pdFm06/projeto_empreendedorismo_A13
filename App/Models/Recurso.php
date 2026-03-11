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

    public function listarPorProjeto($projetoId)
    {
        $query = "
            SELECT
                pr.id,
                pr.projeto_id,
                pr.recurso_id,
                pr.quantidade_afetada,
                r.nome,
                r.tipo,
                r.quantidade,
                r.custo_unitario,
                r.estado
            FROM projeto_recurso pr
            INNER JOIN recursos r ON r.id = pr.recurso_id
            WHERE pr.projeto_id = :projeto_id
            ORDER BY r.nome ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function adicionarAoProjeto($projetoId, $recursoId, $quantidadeAfetada)
    {
        $query = "
            INSERT INTO projeto_recurso
            (projeto_id, recurso_id, quantidade_afetada)
            VALUES
            (:projeto_id, :recurso_id, :quantidade_afetada)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->bindValue(':recurso_id', $recursoId);
        $stmt->bindValue(':quantidade_afetada', $quantidadeAfetada);

        return $stmt->execute();
    }

    public function removerDoProjeto($projetoId, $recursoId)
    {
        $query = "
            DELETE FROM projeto_recurso
            WHERE projeto_id = :projeto_id
            AND recurso_id = :recurso_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->bindValue(':recurso_id', $recursoId);

        return $stmt->execute();
    }
}