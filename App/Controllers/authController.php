<?php

namespace App\Controllers;

use MF\Controller\Action;
use MF\Model\Container;
use App\Lib\Email;

class AuthController extends Action
{
    # Página de login
    public function login()
    {
        $this->view->erro = $this->view->erro ?? '';
        $this->render('login', 'layout1');
    }

    # Página de registo
    public function registar()
    {
        $this->view->erro = $this->view->erro ?? '';
        $this->render('registar', 'layout1');
    }

    # Página de pedir recuperação da password
    public function trocarPalavraPasse()
    {
        $this->view->erro = $this->view->erro ?? '';
        $this->render('forgotpassword', 'layout1');
    }

    # Enviar token por email (recuperação)
    public function enviarToken()
    {

        $email = trim($_POST['email'] ?? '');
        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);

        $user = $utilizador->obterPorEmail();

        #$this->view->mensagem = 'Se o e-mail estiver registado, irá receber um código.';

        if ($user) {
            // Gerar token aleatório de 6 dígitos
            
            #print_r ($user);

            $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Guardar token e expiração
            $utilizador->definirTokenRecuperacao($token);

            // Montar email HTML
            $mensagemHtml = "
                <h2>Recuperação de Palavra-Passe</h2>
                <p>Recebemos um pedido de recuperação de palavra-passe.</p>
                <p>O seu código de verificação é:</p>
                <h1 style='color:#3366cc; font-size:32px;'>$token</h1>
                <p>Este código é válido por 1 hora.</p>
                <p>Se não fez este pedido, ignore este e-mail.</p>
            ";

            // Enviar email com PHPMailer
            Email::enviar($email, 'Código de recuperação de conta', $mensagemHtml);

            session_start(); #1
            $_SESSION['reset_email'] = $email; #1
            $this->render('codigo', 'layout1');
        } else {
            $this->view->mensagem = 'Email inválido';
            $this->render('forgotpassword', 'layout1');
        }

    }

    # Reenviar token se o utilizador não recebeu
    public function reenviarToken()
    {

        #$this->trocarPalavraPasse(); #1
        session_start();

        $email = $_SESSION['reset_email'] ?? null;

        if (!$email) {
            $this->view->mensagem = 'Volte a introduzir o e-mail para receber um novo código.';
            $this->render('forgotpassword', 'layout1');
            return;
        }

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);

        $user = $utilizador->obterPorEmail();
        if (!$user) {
            $this->view->mensagem = 'Volte a introduzir o e-mail.';
            $this->render('forgotpassword', 'layout1');
            return;
        }

        $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $utilizador->definirTokenRecuperacao($token);

        $mensagemHtml = "
            <h2>Recuperação de Palavra-Passe</h2>
            <p>O seu novo código de verificação é:</p>
            <h1 style='color:#3366cc; font-size:32px;'>$token</h1>
            <p>Este código é válido por 1 hora.</p>
        ";

        Email::enviar($email, 'Novo código de recuperação', $mensagemHtml);

        $this->view->mensagem = 'Código reenviado. Verifique o seu e-mail.';
        $this->render('codigo', 'layout1');

    }

    # Validar token e redefinir password
    public function redefinirPassword()
    {

        #$this->render('novapass', 'layout1'); #1
        session_start();

        $token = trim($_POST['token'] ?? '');

        if ($token === '') {
            $this->view->mensagem = 'Introduza o código.';
            $this->render('codigo', 'layout1');
            return;
        }

        $utilizador = Container::getModel('Utilizador');
        $user = $utilizador->obterPorToken($token);

        if (!$user) {
            $this->view->mensagem = 'Código inválido ou expirado.';
            $this->render('codigo', 'layout1');
            return;
        }

        // Token válido -> guardar utilizador na sessão
        $_SESSION['reset_user_id'] = (int)$user['id'];

        $this->render('novapass', 'layout1');
        
    }

    public function enviarNovaPalavraPasse() {
        session_start();

        $novaPwd = $_POST['password'] ?? '';

        $resetUserId = $_SESSION['reset_user_id'] ?? null;
        if (!$resetUserId) {
            $this->view->mensagem = 'Sessão expirada. Volte a pedir recuperação.';
            $this->render('forgotpassword', 'layout1');
            return;
        }

        // Validar força da password (reutiliza a tua função)
        if (preg_match('/\s/', $novaPwd)) {
            $this->view->mensagem = 'A palavra-passe não pode conter espaços.';
            $this->render('novapass', 'layout1');
            return;
        }

        $erroPwd = $this->validarPassword($novaPwd);
        if ($erroPwd) {
            $this->view->mensagem = $erroPwd;
            $this->render('novapass', 'layout1');
            return;
        }

        $novaHash = password_hash($novaPwd, PASSWORD_DEFAULT);

        $utilizador = Container::getModel('Utilizador');
        $utilizador->atualizarPasswordPorId((int)$resetUserId, $novaHash);

        // limpar sessão de reset
        unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);

        $this->view->mensagem = 'Palavra-passe atualizada com sucesso.';
        $this->render('login', 'layout1');

    }

    # Página de inserir o código recebido
    public function mostrarCodigo()
    {
        $this->view->erro = $this->view->erro ?? '';
        $this->render('codigo', 'layout1');
    }

    # Criar conta
    public function criarConta()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        // Evitar espaços nas passwords
        if (preg_match('/\s/', $password)) {
            $this->view->erro = 'A palavra-passe não pode conter espaços.';
            $this->render('registar', 'layout1');
            return;
        }

        // Confirmar se coincidem
        if ($password !== $password2) {
            $this->view->erro = 'As palavras-passe não coincidem.';
            $this->render('registar', 'layout1');
            return;
        }

        // Validar força da password
        $erroPwd = $this->validarPassword($password);
        if ($erroPwd) {
            $this->view->erro = $erroPwd;
            $this->render('registar', 'layout1');
            return;
        }

        // Verificar se já existe
        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);
        if ($utilizador->utilizadorExiste()) {
            $this->view->erro = 'Este e-mail já está registado.';
            $this->render('registar', 'layout1');
            return;
        }

        // Criar utilizador
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $utilizador->__set('password', $passwordHash);
        $utilizador->__set('password2', $passwordHash);

        if ($utilizador->registar()) {
            header('Location: /login');
            exit;
        } else {
            $this->view->erro = 'Erro ao criar conta.';
            $this->render('registar', 'layout1');
        }
    }

    # Validação de força da password
    private function validarPassword($password)
    {
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

    # Autenticar utilizador
    public function autenticar()
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);
        $user = $utilizador->obterPorEmail();

        if ($user && password_verify($password, trim($user['password']))) {
            session_start();
            $_SESSION['id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            header('Location: /dashboard');
            exit;
        } else {
            $this->view->erro = 'E-mail ou palavra-passe incorretos.';
            $this->render('login', 'layout1');
        }
    }

    # Terminar sessão
    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function teste()
    {
        echo "O login está a funcionar!";
    }
}
