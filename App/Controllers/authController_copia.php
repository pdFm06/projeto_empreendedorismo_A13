<?php

namespace App\Controllers;

use MF\Controller\Action;
use MF\Model\Container;
use App\Lib\Email;

class authController extends Action {

    #Página de login
    public function login() {

        #Evitar o warning
        if (!isset($this->view->erro)) {
            $this->view->erro = '';
        }
        $this->render('login', 'layout1');
    }

    #Página de registo
    public function registar() {

        #Evitar o warning
        if (!isset($this->view->erro)) {
            $this->view->erro = ''; 
        }
        $this->render('registar', 'layout1');
    }

    #Página de trocar a palavra-passe
    public function trocarPalavraPasse() {
        #Evitar o warning
        if (!isset($this->view->erro)) {
            $this->view->erro = ''; 
        }
        $this->render('forgotpassword', 'layout1');
    }

    public function enviarToken() {

        echo "Token enviado";

        $email = trim($_POST['email'] ?? '');
        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);

        $user = $utilizador->obterPorEmail();
        $this->view->mensagem = 'Se o e-mail estiver registado, irá receber um código.';

        if ($user) {
            // Gera novo token
            $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $utilizador->definirTokenRecuperacao($token);

            // Monta o email
            $mensagemHtml = "
                <h2>Recuperação de Palavra-Passe</h2>
                <p>Recebemos um pedido de recuperação de palavra-passe.</p>
                <p>O seu código de verificação é:</p>
                <h1 style='color:#3366cc; font-size:32px;'>$token</h1>
                <p>Este código é válido por 1 hora.</p>
                <p>Se não fez este pedido, ignore este e-mail.</p>
            ";

            Email::enviar($email, 'Código de recuperação de conta', $mensagemHtml);
        }

        $this->render('codigo', 'layout1');
    }

    public function reenviarToken() {
        $email = trim($_POST['email'] ?? '');
        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);

        $user = $utilizador->obterPorEmail();

        if ($user) {
            $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $utilizador->definirTokenRecuperacao($token);

            $mensagemHtml = "
                <h2>Reenvio do Código de Recuperação</h2>
                <p>O seu novo código é:</p>
                <h1 style='color:#3366cc; font-size:32px;'>$token</h1>
                <p>Válido por 1 hora.</p>
            ";

            Email::enviar($email, 'Novo código de recuperação', $mensagemHtml);
        }

        $this->view->mensagem = 'Se o e-mail estiver registado, o código foi reenviado.';
        $this->render('codigo', 'layout1');
    }


    public function redefinirPassword() {
        $token = trim($_POST['token'] ?? '');
        $novaPwd = $_POST['password'] ?? '';

        $utilizador = Container::getModel('Utilizador');
        $user = $utilizador->obterPorToken($token);

        if (!$user) {
            $this->view->erro = 'Token inválido ou expirado.';
            $this->render('confirmar_token', 'layout1');
            return;
        }

        $novaHash = password_hash($novaPwd, PASSWORD_DEFAULT);
        $utilizador->atualizarPasswordPorId($user['id'], $novaHash);

        $this->view->mensagem = 'Palavra-passe atualizada com sucesso.';
        $this->render('login', 'layout1');
    }


    #Página de inserir o código
    public function mostrarCodigo() {
        #Evitar o warning
        if (!isset($this->view->erro)) {
                $this->view->erro = ''; 
            }
            $this->render('codigo', 'layout1');
        }

    #Criar conta
    public function criarConta() {

        #Receber dados do formulário
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        //Evitar espaços desnecessários nas passwords
        if (preg_match('/\s/', $password)) {
            $this->view->erro = 'A palavra-passe não pode conter espaços.';
            $this->render('registar', 'layout1');
            return;
        }

        //Confirmar se as passwords coincidem
        if ($password !== $password2) {
            $this->view->erro = 'As palavras-passe não coincidem.';
            $this->render('registar', 'layout1');
            return;
        }

        //Requisitos mínimos da palavra-passe
        $erroPwd = $this->validarPassword($password);
        if ($erroPwd) {
            $this->view->erro = $erroPwd;
            $this->render('registar', 'layout1');
            return;
        }

        //Verifica se o utilizador já existe
        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);
        if ($utilizador->utilizadorExiste()) {
            $this->view->erro = 'Este e-mail é inválido.';
            $this->render('registar', 'layout1');
            return;
        }

        //Criar o utilizador
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $utilizador->__set('password', $passwordHash);
       
        if ($utilizador->registar()) {
            header('Location: /login');
            exit;
        } else {
            $this->view->erro = 'Erro ao criar conta.';
            $this->render('registar', 'layout1');
        }
    }

    #Validação de requisitos mínimos da palavra-passe
    private function validarPassword($password) {

        #Tem de ter pelo menos 8 caracteres, 1 maiúscula, 1 minúscula, 1 número e 1 símbolo
        if (strlen($password) < 8) {
            return 'A palavra-passe deve ter pelo menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'A palavra-passe deve conter pelo menos uma letra maiúscula.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'A palavra-passe deve conter pelo menos uma letra minúscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'A palavra-passe deve conter pelo menos um número.';
        }
        if (!preg_match('/[\W_]/', $password)) {
            return 'A palavra-passe deve conter pelo menos um símbolo (ex: !@#$%).';
        }
        return null;
    }

    #Autenticar o utilizador
    public function autenticar() {

        #Receber dados do formulário
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password']);

        //Obter o utilizador da BD
        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);
        $user = $utilizador->obterPorEmail();

        //Verificar se o utilizador existe e se a password confere
        if ($user && password_verify($password, trim($user['password']))) {
            session_start();

            #Criar uma sessão para o utilizar
            $_SESSION['id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            header('Location: /dashboard');
            exit;
        } else {
            $this->view->erro = 'E-mail ou palavra-passe incorretos.';
            $this->render('login', 'layout1');
        }
    }

    #Terminar sessão
    public function logout() {
        session_start();
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function teste() {
        echo "O login está a funcionar!";
    }
}
