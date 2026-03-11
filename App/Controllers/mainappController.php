<?php 

namespace App\Controllers;

#Recursos
use MF\Controller\Action;
use MF\Model\Container;


class MainappController extends Action {

    public function dashboard() { 

        $this->render('dashboard', 'layout_dashboard');
    }

    # Equipas

    public function equipas()
    {
        $equipa = Container::getModel('Equipa');
        $trabalhador = Container::getModel('Trabalhador');

        $equipas = $equipa->listar();

        foreach ($equipas as &$eq) {
            $eq['membros'] = $equipa->listarMembros($eq['id']);
        }

        $this->view->equipas = $equipas;
        $this->view->trabalhadores = $trabalhador->listar();

        $this->render('equipas', 'layout_dashboard');
    }

    public function criarEquipa()
    {
        $equipa = Container::getModel('Equipa');

        $equipa->__set('nome', $_POST['nome'] ?? '');
        $equipa->__set('especialidade', $_POST['especialidade'] ?? '');
        $equipa->__set('lider_id', $_POST['lider_id'] ?? null);

        $equipaId = $equipa->criar();

        if ($equipaId && !empty($_POST['lider_id'])) {
            $equipa->adicionarMembro($equipaId, $_POST['lider_id']);
        }

        header('Location: /equipas');
        exit;
    }

    public function editarEquipa()
    {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');

        $equipa->__set('id', $id);
        $equipa->__set('nome', $_POST['nome'] ?? '');
        $equipa->__set('especialidade', $_POST['especialidade'] ?? '');
        $equipa->__set('lider_id', $_POST['lider_id'] ?? null);

        $equipa->editar();

        if (!empty($_POST['lider_id'])) {
            // garante que o líder também está como membro
            $membros = $equipa->listarMembros($id);
            $idsMembros = array_column($membros, 'trabalhador_id');

            if (!in_array((int) $_POST['lider_id'], array_map('intval', $idsMembros))) {
                $equipa->adicionarMembro($id, $_POST['lider_id']);
            }
        }

        header('Location: /equipas');
        exit;
    }

    public function eliminarEquipa()
    {
        $id = $_GET['id'] ?? null;

        if ($id) {
            $equipa = Container::getModel('Equipa');
            $equipa->eliminar($id);
        }

        header('Location: /equipas');
        exit;
    }

    public function adicionarMembroEquipa()
    {
        $equipaId = $_POST['equipa_id'] ?? null;
        $trabalhadorId = $_POST['trabalhador_id'] ?? null;

        if (!$equipaId || !$trabalhadorId) {
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');
        $equipa->adicionarMembro($equipaId, $trabalhadorId);

        header('Location: /equipas');
        exit;
    }

    public function removerMembroEquipa()
    {
        $equipaId = $_GET['equipa_id'] ?? null;
        $trabalhadorId = $_GET['trabalhador_id'] ?? null;

        if (!$equipaId || !$trabalhadorId) {
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');
        $equipa->removerMembro($equipaId, $trabalhadorId);

        header('Location: /equipas');
        exit;
    }

    public function recursos() {

        $this->render('recursos', 'layout_dashboard');
    }

    # Trabalhadores

    public function trabalhadores()
    {
        $trabalhador = Container::getModel('Trabalhador');

        $this->view->trabalhadores = $trabalhador->listar();

        $this->render('trabalhadores', 'layout_dashboard');
    }

    public function criarTrabalhador()
    {
        $trabalhador = Container::getModel('Trabalhador');

        $trabalhador->__set('nome', $_POST['nome'] ?? '');
        $trabalhador->__set('email', $_POST['email'] ?? '');
        $trabalhador->__set('telefone', $_POST['telefone'] ?? '');
        $trabalhador->__set('funcao', $_POST['funcao'] ?? '');
        $trabalhador->__set('salario_dia', $_POST['salario_dia'] ?? 0);
        $trabalhador->__set('estado', $_POST['estado'] ?? 'ativo');

        $trabalhador->criar();

        header('Location: /trabalhadores');
        exit;
    }

    public function editarTrabalhador()
    {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            header('Location: /trabalhadores');
            exit;
        }

        $trabalhador = Container::getModel('Trabalhador');

        $trabalhador->__set('id', $id);
        $trabalhador->__set('nome', $_POST['nome'] ?? '');
        $trabalhador->__set('email', $_POST['email'] ?? '');
        $trabalhador->__set('telefone', $_POST['telefone'] ?? '');
        $trabalhador->__set('funcao', $_POST['funcao'] ?? '');
        $trabalhador->__set('salario_dia', $_POST['salario_dia'] ?? 0);
        $trabalhador->__set('estado', $_POST['estado'] ?? 'ativo');

        $trabalhador->editar();

        header('Location: /trabalhadores');
        exit;
    }

    public function eliminarTrabalhador()
    {
        $id = $_GET['id'] ?? null;

        if ($id) {
            $trabalhador = Container::getModel('Trabalhador');
            $trabalhador->eliminar($id);
        }

        header('Location: /trabalhadores');
        exit;
    }

    # Projetos

    public function projetos()
    {
        $projeto = Container::getModel('Projeto');
        $utilizador = Container::getModel('Utilizador');

        $this->view->projetos = $projeto->listar();
        $this->view->gestores = $utilizador->listarTodos();

        $this->render('projetos', 'layout_dashboard');
    }

    public function criarProjeto()
    {
        session_start();

        $projeto = Container::getModel('Projeto');

        $projeto->__set('nome', $_POST['nome'] ?? '');
        $projeto->__set('descricao', $_POST['descricao'] ?? '');
        $projeto->__set('localizacao', $_POST['localizacao'] ?? '');
        $projeto->__set('data_inicio', $_POST['data_inicio'] ?? null);
        $projeto->__set('data_fim_prevista', $_POST['data_fim_prevista'] ?? null);
        $projeto->__set('estado', $_POST['estado'] ?? 'planeado');
        $projeto->__set('orcamento', $_POST['orcamento'] ?? 0);
        $projeto->__set('gestor_id', $_POST['gestor_id'] ?? ($_SESSION['id'] ?? 1));

        $projeto->criar();

        header('Location: /projetos');
        exit;
    }

    public function editarProjeto()
    {
        session_start();

        $id = $_POST['id'] ?? null;

        if (!$id) {
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');

        $projeto->__set('id', $id);
        $projeto->__set('gestor_id', $_POST['gestor_id'] ?? null);
        $projeto->__set('nome', $_POST['nome'] ?? '');
        $projeto->__set('descricao', $_POST['descricao'] ?? '');
        $projeto->__set('localizacao', $_POST['localizacao'] ?? '');
        $projeto->__set('data_inicio', $_POST['data_inicio'] ?? null);
        $projeto->__set('data_fim_prevista', $_POST['data_fim_prevista'] ?? null);
        $projeto->__set('estado', $_POST['estado'] ?? 'planeado');
        $projeto->__set('orcamento', $_POST['orcamento'] ?? 0);

        $projeto->editar();

        header('Location: /projetos');
        exit;
    }


    public function eliminarProjeto()
    {
        $id = $_GET['id'] ?? null;

        if ($id) {
            $projeto = Container::getModel('Projeto');
            $projeto->eliminar($id);
        }

        header('Location: /projetos');
        exit;
    }

}


?>