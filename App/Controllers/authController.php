<?php

namespace App\Controllers;

use MF\Controller\Action;
use MF\Model\Container;
use App\Lib\Email;
use App\Lib\Flash;

class AuthController extends Action
{
    # Página de login
    public function login()
    {
        if (isset($_SESSION['id'])) {
            header('Location: /dashboard');
            exit;
        }

        $this->render('login', 'layout1');
    }

    # Página de registo
    public function registar()
    {
        if (isset($_SESSION['id'])) {
            header('Location: /dashboard');
            exit;
        }

        $this->render('registar', 'layout1');
    }

    public function autenticar()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            Flash::set('warning', 'Preencha o e-mail e a palavra-passe.');
            header('Location: /login');
            exit;
        }

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);
        $user = $utilizador->obterPorEmail();

        if ($user && password_verify($password, trim($user['password']))) {
            session_regenerate_id(true);

            $_SESSION['id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            Flash::set('success', 'Sessão iniciada com sucesso.');
            header('Location: /dashboard');
            exit;
        }

        Flash::set('danger', 'E-mail ou palavra-passe incorretos.');
        header('Location: /login');
        exit;
    }

    public function logout()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: /login');
        exit;
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

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('warning', 'Introduza um e-mail válido.');
            header('Location: /trocar_palavra_passe');
            exit;
        }

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);
        $user = $utilizador->obterPorEmail();

        if ($user) {
            $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $utilizador->definirTokenRecuperacao($token);

            $mensagemHtml = "
                <h2>Recuperação de Palavra-Passe</h2>
                <p>Recebemos um pedido de recuperação de palavra-passe.</p>
                <p>O seu código de verificação é:</p>
                <h1 style='color:#3366cc; font-size:32px;'>$token</h1>
                <p>Este código é válido por 1 hora.</p>
                <p>Se não fez este pedido, ignore este e-mail.</p>
            ";

            Email::enviar($email, 'Código de recuperação de conta', $mensagemHtml);
            $_SESSION['reset_email'] = $email;
        }

        Flash::set('info', 'Se o e-mail estiver registado, irá receber um código.');
        header('Location: /mostrar_codigo');
        exit;
    }

    # Reenviar token se o utilizador não recebeu
    public function reenviarToken()
    {
        $email = $_SESSION['reset_email'] ?? null;

        if (!$email) {
            Flash::set('warning', 'Volte a introduzir o e-mail para receber um novo código.');
            header('Location: /trocar_palavra_passe');
            exit;
        }

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);
        $user = $utilizador->obterPorEmail();

        if (!$user) {
            Flash::set('warning', 'Volte a introduzir o e-mail.');
            header('Location: /trocar_palavra_passe');
            exit;
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

        Flash::set('success', 'Código reenviado. Verifique o seu e-mail.');
        header('Location: /mostrar_codigo');
        exit;
    }

    # Validar token e redefinir password
    public function redefinirPassword()
    {
        $token = trim($_POST['token'] ?? '');

        if ($token === '') {
            Flash::set('warning', 'Introduza o código.');
            header('Location: /mostrar_codigo');
            exit;
        }

        $utilizador = Container::getModel('Utilizador');
        $user = $utilizador->obterPorToken($token);

        if (!$user) {
            Flash::set('danger', 'Código inválido ou expirado.');
            header('Location: /mostrar_codigo');
            exit;
        }

        $_SESSION['reset_user_id'] = (int)$user['id'];

        $this->render('novapass', 'layout1');
    }

    public function enviarNovaPalavraPasse()
    {
        $novaPwd = $_POST['password'] ?? '';
        $resetUserId = $_SESSION['reset_user_id'] ?? null;

        if (!$resetUserId) {
            Flash::set('warning', 'Sessão expirada. Volte a pedir recuperação.');
            header('Location: /trocar_palavra_passe');
            exit;
        }

        if (preg_match('/\s/', $novaPwd)) {
            Flash::set('warning', 'A palavra-passe não pode conter espaços.');
            header('Location: /redefinirPassword');
            exit;
        }

        $erroPwd = $this->validarPassword($novaPwd);
        if ($erroPwd) {
            Flash::set('warning', $erroPwd);
            header('Location: /redefinirPassword');
            exit;
        }

        $novaHash = password_hash($novaPwd, PASSWORD_DEFAULT);

        $utilizador = Container::getModel('Utilizador');
        $utilizador->atualizarPasswordPorId((int)$resetUserId, $novaHash);

        unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);

        Flash::set('success', 'Palavra-passe atualizada com sucesso.');
        header('Location: /login');
        exit;
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

        if ($email === '') {
            Flash::set('warning', 'O e-mail é obrigatório.');
            header('Location: /registar');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('warning', 'Introduza um e-mail válido.');
            header('Location: /registar');
            exit;
        }

        if (preg_match('/\s/', $password)) {
            Flash::set('warning', 'A palavra-passe não pode conter espaços.');
            header('Location: /registar');
            exit;
        }

        if ($password !== $password2) {
            Flash::set('warning', 'As palavras-passe não coincidem.');
            header('Location: /registar');
            exit;
        }

        $erroPwd = $this->validarPassword($password);
        if ($erroPwd) {
            Flash::set('warning', $erroPwd);
            header('Location: /registar');
            exit;
        }

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $email);

        if ($utilizador->utilizadorExiste()) {
            Flash::set('warning', 'Este e-mail já está registado.');
            header('Location: /registar');
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $utilizador->__set('password', $passwordHash);
        $utilizador->__set('password2', $passwordHash);

        if ($utilizador->registar()) {
            Flash::set('success', 'Conta criada com sucesso. Já pode iniciar sessão.');
            header('Location: /login');
            exit;
        }

        Flash::set('danger', 'Erro ao criar conta.');
        header('Location: /registar');
        exit;
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


    public function teste()
    {
        echo "O login está a funcionar!";
    }
}
