<?php

namespace App\Models;

use MF\Model\Model;

class NotaProjeto extends Model
{
    private $id;
    private $projeto_id;
    private $utilizador_id;
    private $titulo;
    private $conteudo;

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
                id,
                projeto_id,
                utilizador_id,
                titulo,
                conteudo,
                criado_em,
                atualizado_em
            FROM notas_projeto
            WHERE projeto_id = :projeto_id
              AND utilizador_id = :utilizador_id
            ORDER BY criado_em DESC, id DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $projetoId);
        $stmt->bindValue(':utilizador_id', $utilizadorId);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obterPorIdEUtilizador($id, $utilizadorId)
    {
        $query = "
            SELECT *
            FROM notas_projeto
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
            INSERT INTO notas_projeto
            (projeto_id, utilizador_id, titulo, conteudo)
            VALUES
            (:projeto_id, :utilizador_id, :titulo, :conteudo)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':projeto_id', $this->__get('projeto_id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));
        $stmt->bindValue(':titulo', $this->__get('titulo'));
        $stmt->bindValue(':conteudo', $this->__get('conteudo'));

        return $stmt->execute();
    }

    public function editar()
    {
        $query = "
            UPDATE notas_projeto
            SET
                titulo = :titulo,
                conteudo = :conteudo,
                atualizado_em = NOW()
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':titulo', $this->__get('titulo'));
        $stmt->bindValue(':conteudo', $this->__get('conteudo'));
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->bindValue(':utilizador_id', $this->__get('utilizador_id'));

        return $stmt->execute();
    }

    public function eliminar($id, $utilizadorId)
    {
        $query = "
            DELETE FROM notas_projeto
            WHERE id = :id
              AND utilizador_id = :utilizador_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':utilizador_id', $utilizadorId);

        return $stmt->execute();
    }
}