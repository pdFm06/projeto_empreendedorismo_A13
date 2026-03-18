<?php 

namespace App\Controllers;

use MF\Controller\Action;
use MF\Model\Container;
use App\Lib\Flash;

class MainappController extends Action
{
    private function validarAutenticacao()
    {
        if (!isset($_SESSION['id'])) {
            Flash::set('warning', 'Tem de iniciar sessão para aceder a essa página.');
            header('Location: /login');
            exit;
        }
    }

    # Dashboard
    public function dashboard()
    {
        $this->validarAutenticacao();

        $projeto = Container::getModel('Projeto');
        $trabalhador = Container::getModel('Trabalhador');
        $equipa = Container::getModel('Equipa');
        $recurso = Container::getModel('Recurso');

        $projetos = $projeto->listarPorUtilizador($_SESSION['id']);
        $trabalhadores = $trabalhador->listarPorUtilizador($_SESSION['id']);
        $equipas = $equipa->listarPorUtilizador($_SESSION['id']);
        $recursos = $recurso->listarPorUtilizador($_SESSION['id']);

        $totalProjetos = count($projetos);
        $projetosEmExecucao = 0;
        $projetosConcluidos = 0;
        $orcamentoTotal = 0;

        foreach ($projetos as $p) {
            if (($p['estado'] ?? '') === 'em_execucao') {
                $projetosEmExecucao++;
            }

            if (($p['estado'] ?? '') === 'concluido') {
                $projetosConcluidos++;
            }

            $orcamentoTotal += (float)($p['orcamento'] ?? 0);
        }

        $totalTrabalhadores = count($trabalhadores);
        $trabalhadoresAtivos = 0;

        foreach ($trabalhadores as $t) {
            if (($t['estado'] ?? '') === 'ativo') {
                $trabalhadoresAtivos++;
            }
        }

        $totalEquipas = count($equipas);

        $totalRecursos = count($recursos);
        $recursosBaixoStock = 0;
        $recursosEsgotados = 0;

        foreach ($recursos as $r) {
            $quantidade = (int)($r['quantidade'] ?? 0);

            if ($quantidade === 0) {
                $recursosEsgotados++;
            } elseif ($quantidade <= 10) {
                $recursosBaixoStock++;
            }
        }

        $this->view->dashboard = [
            'total_projetos' => $totalProjetos,
            'projetos_em_execucao' => $projetosEmExecucao,
            'projetos_concluidos' => $projetosConcluidos,
            'orcamento_total' => $orcamentoTotal,
            'total_trabalhadores' => $totalTrabalhadores,
            'trabalhadores_ativos' => $trabalhadoresAtivos,
            'total_equipas' => $totalEquipas,
            'total_recursos' => $totalRecursos,
            'recursos_baixo_stock' => $recursosBaixoStock,
            'recursos_esgotados' => $recursosEsgotados
        ];

        $this->render('dashboard', 'layout_dashboard');
    }

    # Equipas
    public function equipas()
    {
        $this->validarAutenticacao();

        $equipa = Container::getModel('Equipa');
        $trabalhador = Container::getModel('Trabalhador');

        $equipas = $equipa->listarPorUtilizador($_SESSION['id']);

        foreach ($equipas as &$eq) {
            $eq['membros'] = $equipa->listarMembros($eq['id']);
        }

        $this->view->equipas = $equipas;
        $this->view->trabalhadores = $trabalhador->listarPorUtilizador($_SESSION['id']);

        $this->render('equipas', 'layout_dashboard');
    }

    public function criarEquipa()
    {
        $this->validarAutenticacao();

        $equipa = Container::getModel('Equipa');

        $equipa->__set('nome', $_POST['nome'] ?? '');
        $equipa->__set('especialidade', $_POST['especialidade'] ?? '');
        $equipa->__set('lider_id', $_POST['lider_id'] ?? null);
        $equipa->__set('utilizador_id', $_SESSION['id']);

        $equipaId = $equipa->criar();

        if ($equipaId && !empty($_POST['lider_id'])) {
            $equipa->adicionarMembro($equipaId, $_POST['lider_id']);
        }

        Flash::set('success', 'Equipa criada com sucesso.');
        header('Location: /equipas');
        exit;
    }

    public function editarEquipa()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;

        if (!$id) {
            Flash::set('warning', 'Equipa inválida.');
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');
        $equipaExistente = $equipa->obterPorIdEUtilizador($id, $_SESSION['id']);

        if (!$equipaExistente) {
            Flash::set('danger', 'Não tem permissão para editar esta equipa.');
            header('Location: /equipas');
            exit;
        }

        $equipa->__set('id', $id);
        $equipa->__set('nome', $_POST['nome'] ?? '');
        $equipa->__set('especialidade', $_POST['especialidade'] ?? '');
        $equipa->__set('lider_id', $_POST['lider_id'] ?? null);
        $equipa->__set('utilizador_id', $_SESSION['id']);

        $equipa->editar();

        if (!empty($_POST['lider_id'])) {
            $membros = $equipa->listarMembros($id);
            $idsMembros = array_column($membros, 'trabalhador_id');

            if (!in_array((int) $_POST['lider_id'], array_map('intval', $idsMembros))) {
                $equipa->adicionarMembro($id, $_POST['lider_id']);
            }
        }

        Flash::set('success', 'Equipa atualizada com sucesso.');
        header('Location: /equipas');
        exit;
    }

    public function eliminarEquipa()
    {
        $this->validarAutenticacao();

        $id = $_GET['id'] ?? null;

        if ($id) {
            $equipa = Container::getModel('Equipa');
            $equipa->eliminar($id, $_SESSION['id']);
            Flash::set('success', 'Equipa eliminada com sucesso.');
        }

        header('Location: /equipas');
        exit;
    }

    public function adicionarMembroEquipa()
    {
        $this->validarAutenticacao();

        $equipaId = $_POST['equipa_id'] ?? null;
        $trabalhadorId = $_POST['trabalhador_id'] ?? null;

        if (!$equipaId || !$trabalhadorId) {
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');
        $equipaExistente = $equipa->obterPorIdEUtilizador($equipaId, $_SESSION['id']);

        if (!$equipaExistente) {
            Flash::set('danger', 'Não tem permissão para alterar esta equipa.');
            header('Location: /equipas');
            exit;
        }

        $equipa->adicionarMembro($equipaId, $trabalhadorId);

        Flash::set('success', 'Membro adicionado com sucesso.');
        header('Location: /equipas');
        exit;
    }

    public function removerMembroEquipa()
    {
        $this->validarAutenticacao();

        $equipaId = $_GET['equipa_id'] ?? null;
        $trabalhadorId = $_GET['trabalhador_id'] ?? null;

        if (!$equipaId || !$trabalhadorId) {
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');
        $equipaExistente = $equipa->obterPorIdEUtilizador($equipaId, $_SESSION['id']);

        if (!$equipaExistente) {
            Flash::set('danger', 'Não tem permissão para alterar esta equipa.');
            header('Location: /equipas');
            exit;
        }

        $equipa->removerMembro($equipaId, $trabalhadorId);

        Flash::set('success', 'Membro removido com sucesso.');
        header('Location: /equipas');
        exit;
    }

    # Recursos
    public function recursos()
    {
        $this->validarAutenticacao();

        $recurso = Container::getModel('Recurso');
        $this->view->recursos = $recurso->listarPorUtilizador($_SESSION['id']);

        $this->render('recursos', 'layout_dashboard');
    }

    public function criarRecurso()
    {
        $this->validarAutenticacao();

        $recurso = Container::getModel('Recurso');

        $recurso->__set('nome', $_POST['nome'] ?? '');
        $recurso->__set('tipo', $_POST['tipo'] ?? 'material');
        $recurso->__set('quantidade', $_POST['quantidade'] ?? 0);
        $recurso->__set('custo_unitario', $_POST['custo_unitario'] ?? 0);
        $recurso->__set('estado', $_POST['estado'] ?? 'disponivel');
        $recurso->__set('utilizador_id', $_SESSION['id']);

        $recurso->criar();

        Flash::set('success', 'Recurso criado com sucesso.');
        header('Location: /recursos');
        exit;
    }

    public function editarRecurso()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;

        if (!$id) {
            header('Location: /recursos');
            exit;
        }

        $recurso = Container::getModel('Recurso');
        $recursoExistente = $recurso->obterPorIdEUtilizador($id, $_SESSION['id']);

        if (!$recursoExistente) {
            Flash::set('danger', 'Não tem permissão para editar este recurso.');
            header('Location: /recursos');
            exit;
        }

        $recurso->__set('id', $id);
        $recurso->__set('nome', $_POST['nome'] ?? '');
        $recurso->__set('tipo', $_POST['tipo'] ?? 'material');
        $recurso->__set('quantidade', $_POST['quantidade'] ?? 0);
        $recurso->__set('custo_unitario', $_POST['custo_unitario'] ?? 0);
        $recurso->__set('estado', $_POST['estado'] ?? 'disponivel');
        $recurso->__set('utilizador_id', $_SESSION['id']);

        $recurso->editar();

        Flash::set('success', 'Recurso atualizado com sucesso.');
        header('Location: /recursos');
        exit;
    }

    public function eliminarRecurso()
    {
        $this->validarAutenticacao();

        $id = $_GET['id'] ?? null;

        if ($id) {
            $recurso = Container::getModel('Recurso');
            $recurso->eliminar($id, $_SESSION['id']);
            Flash::set('success', 'Recurso eliminado com sucesso.');
        }

        header('Location: /recursos');
        exit;
    }

    public function atualizarQuantidadeRecurso()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;
        $quantidade = $_POST['quantidade'] ?? null;

        if ($id === null || $quantidade === null) {
            header('Location: /recursos');
            exit;
        }

        $recurso = Container::getModel('Recurso');
        $recurso->atualizarQuantidade($id, max(0, (int)$quantidade), $_SESSION['id']);

        Flash::set('success', 'Quantidade atualizada com sucesso.');
        header('Location: /recursos');
        exit;
    }

    # Trabalhadores
    public function trabalhadores()
    {
        $this->validarAutenticacao();

        $trabalhador = Container::getModel('Trabalhador');
        $this->view->trabalhadores = $trabalhador->listarPorUtilizador($_SESSION['id']);

        $this->render('trabalhadores', 'layout_dashboard');
    }

    public function criarTrabalhador()
    {
        $this->validarAutenticacao();

        $trabalhador = Container::getModel('Trabalhador');

        $trabalhador->__set('nome', $_POST['nome'] ?? '');
        $trabalhador->__set('email', $_POST['email'] ?? '');
        $trabalhador->__set('telefone', $_POST['telefone'] ?? '');
        $trabalhador->__set('funcao', $_POST['funcao'] ?? '');
        $trabalhador->__set('salario_dia', $_POST['salario_dia'] ?? 0);
        $trabalhador->__set('estado', $_POST['estado'] ?? 'ativo');
        $trabalhador->__set('utilizador_id', $_SESSION['id']);

        $trabalhador->criar();

        Flash::set('success', 'Trabalhador criado com sucesso.');
        header('Location: /trabalhadores');
        exit;
    }

    public function editarTrabalhador()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;

        if (!$id) {
            header('Location: /trabalhadores');
            exit;
        }

        $trabalhador = Container::getModel('Trabalhador');
        $trabalhadorExistente = $trabalhador->obterPorIdEUtilizador($id, $_SESSION['id']);

        if (!$trabalhadorExistente) {
            Flash::set('danger', 'Não tem permissão para editar este trabalhador.');
            header('Location: /trabalhadores');
            exit;
        }

        $trabalhador->__set('id', $id);
        $trabalhador->__set('nome', $_POST['nome'] ?? '');
        $trabalhador->__set('email', $_POST['email'] ?? '');
        $trabalhador->__set('telefone', $_POST['telefone'] ?? '');
        $trabalhador->__set('funcao', $_POST['funcao'] ?? '');
        $trabalhador->__set('salario_dia', $_POST['salario_dia'] ?? 0);
        $trabalhador->__set('estado', $_POST['estado'] ?? 'ativo');
        $trabalhador->__set('utilizador_id', $_SESSION['id']);

        $trabalhador->editar();

        Flash::set('success', 'Trabalhador atualizado com sucesso.');
        header('Location: /trabalhadores');
        exit;
    }

    public function eliminarTrabalhador()
    {
        $this->validarAutenticacao();

        $id = $_GET['id'] ?? null;

        if ($id) {
            $trabalhador = Container::getModel('Trabalhador');
            $trabalhador->eliminar($id, $_SESSION['id']);
            Flash::set('success', 'Trabalhador eliminado com sucesso.');
        }

        header('Location: /trabalhadores');
        exit;
    }

    # Projetos
    public function projetos()
    {
        $this->validarAutenticacao();

        $projeto = Container::getModel('Projeto');
        $utilizador = Container::getModel('Utilizador');
        $equipa = Container::getModel('Equipa');
        $recurso = Container::getModel('Recurso');

        $projetos = $projeto->listarPorUtilizador($_SESSION['id']);

        foreach ($projetos as &$p) {
            $p['equipas'] = $projeto->listarEquipas($p['id']);
            $p['recursos'] = $recurso->listarPorProjeto($p['id']);
        }

        $this->view->projetos = $projetos;
        $this->view->gestores = $utilizador->listarTodos();
        $this->view->equipasDisponiveis = $equipa->listarPorUtilizador($_SESSION['id']);
        $this->view->recursosDisponiveis = $recurso->listarPorUtilizador($_SESSION['id']);

        $this->render('projetos', 'layout_dashboard');
    }

    public function criarProjeto()
    {
        $this->validarAutenticacao();

        if (empty(trim($_POST['nome'] ?? ''))) {
            Flash::set('warning', 'O nome do projeto é obrigatório.');
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');

        $projeto->__set('nome', $_POST['nome'] ?? '');
        $projeto->__set('descricao', $_POST['descricao'] ?? '');
        $projeto->__set('localizacao', $_POST['localizacao'] ?? '');
        $projeto->__set('data_inicio', $_POST['data_inicio'] ?? null);
        $projeto->__set('data_fim_prevista', $_POST['data_fim_prevista'] ?? null);
        $projeto->__set('estado', $_POST['estado'] ?? 'planeado');
        $projeto->__set('orcamento', $_POST['orcamento'] ?? 0);
        $projeto->__set('gestor_id', $_SESSION['id']);
        $projeto->__set('utilizador_id', $_SESSION['id']);

        if ($projeto->criar()) {
            Flash::set('success', 'Projeto criado com sucesso.');
        } else {
            Flash::set('danger', 'Não foi possível criar o projeto.');
        }

        header('Location: /projetos');
        exit;
    }

    public function editarProjeto()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;

        if (!$id) {
            Flash::set('warning', 'Projeto inválido.');
            header('Location: /projetos');
            exit;
        }

        if (empty(trim($_POST['nome'] ?? ''))) {
            Flash::set('warning', 'O nome do projeto é obrigatório.');
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');
        $projetoExistente = $projeto->obterPorIdEUtilizador($id, $_SESSION['id']);

        if (!$projetoExistente) {
            Flash::set('danger', 'Não tem permissão para editar este projeto.');
            header('Location: /projetos');
            exit;
        }

        $projeto->__set('id', $id);
        $projeto->__set('gestor_id', $_SESSION['id']);
        $projeto->__set('utilizador_id', $_SESSION['id']);
        $projeto->__set('nome', $_POST['nome'] ?? '');
        $projeto->__set('descricao', $_POST['descricao'] ?? '');
        $projeto->__set('localizacao', $_POST['localizacao'] ?? '');
        $projeto->__set('data_inicio', $_POST['data_inicio'] ?? null);
        $projeto->__set('data_fim_prevista', $_POST['data_fim_prevista'] ?? null);
        $projeto->__set('estado', $_POST['estado'] ?? 'planeado');
        $projeto->__set('orcamento', $_POST['orcamento'] ?? 0);

        if ($projeto->editar()) {
            Flash::set('success', 'Projeto atualizado com sucesso.');
        } else {
            Flash::set('danger', 'Não foi possível atualizar o projeto.');
        }

        header('Location: /projetos');
        exit;
    }

    public function eliminarProjeto()
    {
        $this->validarAutenticacao();

        $id = $_GET['id'] ?? null;

        if (!$id) {
            Flash::set('warning', 'Projeto inválido.');
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');
        $projetoExistente = $projeto->obterPorIdEUtilizador($id, $_SESSION['id']);

        if (!$projetoExistente) {
            Flash::set('danger', 'Não tem permissão para eliminar este projeto.');
            header('Location: /projetos');
            exit;
        }

        if ($projeto->eliminar($id, $_SESSION['id'])) {
            Flash::set('success', 'Projeto eliminado com sucesso.');
        } else {
            Flash::set('danger', 'Não foi possível eliminar o projeto.');
        }

        header('Location: /projetos');
        exit;
    }

    public function adicionarEquipaProjeto()
    {
        $this->validarAutenticacao();

        $projetoId = $_POST['projeto_id'] ?? null;
        $equipaId = $_POST['equipa_id'] ?? null;

        if (!$projetoId || !$equipaId) {
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');
        $equipa = Container::getModel('Equipa');

        $projetoExistente = $projeto->obterPorIdEUtilizador($projetoId, $_SESSION['id']);
        $equipaExistente = $equipa->obterPorIdEUtilizador($equipaId, $_SESSION['id']);

        if (!$projetoExistente || !$equipaExistente) {
            Flash::set('danger', 'Associação inválida.');
            header('Location: /projetos');
            exit;
        }

        $projeto->adicionarEquipa($projetoId, $equipaId);

        Flash::set('success', 'Equipa associada ao projeto com sucesso.');
        header('Location: /projetos');
        exit;
    }

    public function removerEquipaProjeto()
    {
        $this->validarAutenticacao();

        $projetoId = $_GET['projeto_id'] ?? null;
        $equipaId = $_GET['equipa_id'] ?? null;

        if (!$projetoId || !$equipaId) {
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');
        $projetoExistente = $projeto->obterPorIdEUtilizador($projetoId, $_SESSION['id']);

        if (!$projetoExistente) {
            Flash::set('danger', 'Não tem permissão para alterar este projeto.');
            header('Location: /projetos');
            exit;
        }

        $projeto->removerEquipa($projetoId, $equipaId);

        Flash::set('success', 'Equipa removida do projeto com sucesso.');
        header('Location: /projetos');
        exit;
    }

    public function adicionarRecursoProjeto()
    {
        $this->validarAutenticacao();

        $projetoId = $_POST['projeto_id'] ?? null;
        $recursoId = $_POST['recurso_id'] ?? null;
        $quantidadeAfetada = $_POST['quantidade_afetada'] ?? null;

        if (!$projetoId || !$recursoId || !$quantidadeAfetada) {
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');
        $recurso = Container::getModel('Recurso');

        $projetoExistente = $projeto->obterPorIdEUtilizador($projetoId, $_SESSION['id']);
        $recursoExistente = $recurso->obterPorIdEUtilizador($recursoId, $_SESSION['id']);

        if (!$projetoExistente || !$recursoExistente) {
            Flash::set('danger', 'Associação inválida.');
            header('Location: /projetos');
            exit;
        }

        $recurso->adicionarAoProjeto($projetoId, $recursoId, max(1, (int)$quantidadeAfetada));

        Flash::set('success', 'Recurso associado ao projeto com sucesso.');
        header('Location: /projetos');
        exit;
    }

    public function removerRecursoProjeto()
    {
        $this->validarAutenticacao();

        $projetoId = $_GET['projeto_id'] ?? null;
        $recursoId = $_GET['recurso_id'] ?? null;

        if (!$projetoId || !$recursoId) {
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');
        $projetoExistente = $projeto->obterPorIdEUtilizador($projetoId, $_SESSION['id']);

        if (!$projetoExistente) {
            Flash::set('danger', 'Não tem permissão para alterar este projeto.');
            header('Location: /projetos');
            exit;
        }

        $recurso = Container::getModel('Recurso');
        $recurso->removerDoProjeto($projetoId, $recursoId);

        Flash::set('success', 'Recurso removido do projeto com sucesso.');
        header('Location: /projetos');
        exit;
    }
}