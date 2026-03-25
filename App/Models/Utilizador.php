<?php

namespace App\Models;

use MF\Model\Model;
use PDO;

class Utilizador extends Model {
    // Propriedades correspondentes às colunas da BD
    private $id;
    private $email;
    private $password;
    private $password2;

    // Getters e Setters
    public function __get($atributo) {
        return $this->$atributo;
    }

    public function __set($atributo, $valor){
        $this->$atributo = $valor;
    }

    public function registar() {
        $query = "INSERT INTO utilizadores (email, password) VALUES (:email, :password)";
        $stmt = $this->db->prepare($query);

        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->bindValue(':password', $this->__get('password'));

        return $stmt->execute();
    }

    #Verificar se já existe um utilizador com este e-mail
    public function utilizadorExiste() {
        $query = "SELECT id FROM utilizadores WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    #Obter utilizador por e-mail (para autenticação)     
    public function obterPorEmail() {
        $query = "SELECT * FROM utilizadores WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':email', $this->__get('email'));
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    #Obter utilizador por ID
    public function obterPorId() {
        $query = "SELECT id, email FROM utilizadores WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $this->__get('id'));
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function definirTokenRecuperacao(string $token): bool
    {
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);

        $sql = "UPDATE utilizadores 
                SET reset_token_hash = :token_hash,
                    reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token_hash', $tokenHash);
        $stmt->bindValue(':email', $this->__get('email'));

        return $stmt->execute();
    }

    public function obterPorToken(string $token)
    {
        $sql = "SELECT * FROM utilizadores 
                WHERE reset_token_hash IS NOT NULL
                AND reset_token_expires_at > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            if (password_verify($token, $user['reset_token_hash'])) {
                return $user;
            }
        }

        return false;
    }

    public function atualizarPasswordPorId(int $id, string $passwordHash)
    {
        $sql = "UPDATE utilizadores 
                SET password = :password,
                    reset_token_hash = NULL,
                    reset_token_expires_at = NULL
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':password', $passwordHash);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function limparToken()
    {
        $sql = "UPDATE utilizadores
                SET reset_token_hash = NULL,
                    reset_token_expires_at = NULL
                WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $this->__get('email'));

        return $stmt->execute();
    }

    public function listarTodos() {
        $query = "SELECT id, email FROM utilizadores ORDER BY email ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterContaPorId($id)
    {
        $query = "SELECT id, email, tema, mfa_ativo FROM utilizadores WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function atualizarTema($id, $tema)
    {
        $query = "UPDATE utilizadores SET tema = :tema WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':tema', $tema);
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }

    public function atualizarPasswordConta($id, $passwordHash)
    {
        $query = "UPDATE utilizadores SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':password', $passwordHash);
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }

    public function eliminarConta($id)
    {
        $query = "DELETE FROM utilizadores WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }
}
