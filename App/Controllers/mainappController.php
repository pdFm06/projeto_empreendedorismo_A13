<?php 

namespace App\Controllers;

#Recursos
use MF\Controller\Action;
use MF\Model\Container;


class MainappController extends Action {

    public function dashboard() { 

        $this->render('dashboard', 'layout_dashboard');
    }

    public function equipas() {

        $this->render('equipas', 'layout_dashboard');
    }

    public function recursos() {

        $this->render('recursos', 'layout_dashboard');
    }

    public function trabalhadores() {

        $this->render('trabalhadores', 'layout_dashboard');
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