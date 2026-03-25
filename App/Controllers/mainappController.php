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

        $nome = trim($_POST['nome'] ?? '');
        $especialidade = trim($_POST['especialidade'] ?? '');
        $liderId = $_POST['lider_id'] ?? null;

        if ($nome === '') {
            Flash::set('warning', 'O nome da equipa é obrigatório.');
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');
        $trabalhador = Container::getModel('Trabalhador');

        if (!empty($liderId)) {
            $lider = $trabalhador->obterPorIdEUtilizador($liderId, $_SESSION['id']);

            if (!$lider) {
                Flash::set('warning', 'Líder inválido.');
                header('Location: /equipas');
                exit;
            }

            if (($lider['estado'] ?? '') !== 'ativo') {
                Flash::set('warning', 'Só pode definir um trabalhador ativo como líder da equipa.');
                header('Location: /equipas');
                exit;
            }
        }

        $equipa->__set('nome', $nome);
        $equipa->__set('especialidade', $especialidade);
        $equipa->__set('lider_id', $liderId ?: null);
        $equipa->__set('utilizador_id', $_SESSION['id']);

        $equipaId = $equipa->criar();

        if ($equipaId && !empty($liderId)) {
            $equipa->adicionarMembro($equipaId, $liderId);
        }

        Flash::set('success', 'Equipa criada com sucesso.');
        header('Location: /equipas');
        exit;
    }

    public function editarEquipa()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $especialidade = trim($_POST['especialidade'] ?? '');
        $liderId = $_POST['lider_id'] ?? null;

        if (!$id) {
            Flash::set('warning', 'Equipa inválida.');
            header('Location: /equipas');
            exit;
        }

        if ($nome === '') {
            Flash::set('warning', 'O nome da equipa é obrigatório.');
            header('Location: /equipas');
            exit;
        }

        $equipa = Container::getModel('Equipa');
        $trabalhador = Container::getModel('Trabalhador');

        $equipaExistente = $equipa->obterPorIdEUtilizador($id, $_SESSION['id']);
        if (!$equipaExistente) {
            Flash::set('danger', 'Não tem permissão para editar esta equipa.');
            header('Location: /equipas');
            exit;
        }

        if (!empty($liderId)) {
            $lider = $trabalhador->obterPorIdEUtilizador($liderId, $_SESSION['id']);

            if (!$lider) {
                Flash::set('warning', 'Líder inválido.');
                header('Location: /equipas');
                exit;
            }

            if (($lider['estado'] ?? '') !== 'ativo') {
                Flash::set('warning', 'Só pode definir um trabalhador ativo como líder da equipa.');
                header('Location: /equipas');
                exit;
            }
        }

        $equipa->__set('id', $id);
        $equipa->__set('nome', $nome);
        $equipa->__set('especialidade', $especialidade);
        $equipa->__set('lider_id', $liderId ?: null);
        $equipa->__set('utilizador_id', $_SESSION['id']);

        $equipa->editar();

        if (!empty($liderId)) {
            $membros = $equipa->listarMembros($id);
            $idsMembros = array_column($membros, 'trabalhador_id');

            if (!in_array((int)$liderId, array_map('intval', $idsMembros))) {
                $equipa->adicionarMembro($id, $liderId);
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

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $funcao = trim($_POST['funcao'] ?? '');
        $salarioDia = (float)($_POST['salario_dia'] ?? 0);
        $estado = $_POST['estado'] ?? 'ativo';

        if ($nome === '') {
            Flash::set('warning', 'O nome do trabalhador é obrigatório.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($email === '') {
            Flash::set('warning', 'O email do trabalhador é obrigatório.');
            header('Location: /trabalhadores');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('warning', 'Introduza um email válido.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($funcao === '') {
            Flash::set('warning', 'A função do trabalhador é obrigatória.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($salarioDia <= 0) {
            Flash::set('warning', 'O salário por dia tem de ser superior a 0.');
            header('Location: /trabalhadores');
            exit;
        }

        $trabalhador = Container::getModel('Trabalhador');

        $duplicado = $trabalhador->obterPorEmailEUtilizador($email, $_SESSION['id']);
        if ($duplicado) {
            Flash::set('warning', 'Já existe um trabalhador com esse email na sua empresa.');
            header('Location: /trabalhadores');
            exit;
        }

        $trabalhador->__set('nome', $nome);
        $trabalhador->__set('email', $email);
        $trabalhador->__set('telefone', $telefone);
        $trabalhador->__set('funcao', $funcao);
        $trabalhador->__set('salario_dia', $salarioDia);
        $trabalhador->__set('estado', $estado);
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

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $funcao = trim($_POST['funcao'] ?? '');
        $salarioDia = (float)($_POST['salario_dia'] ?? 0);
        $estado = $_POST['estado'] ?? 'ativo';

        if ($nome === '') {
            Flash::set('warning', 'O nome do trabalhador é obrigatório.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($email === '') {
            Flash::set('warning', 'O email do trabalhador é obrigatório.');
            header('Location: /trabalhadores');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('warning', 'Introduza um email válido.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($funcao === '') {
            Flash::set('warning', 'A função do trabalhador é obrigatória.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($salarioDia <= 0) {
            Flash::set('warning', 'O salário por dia tem de ser superior a 0.');
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

        $duplicado = $trabalhador->obterPorEmailEUtilizadorExcetoId($email, $_SESSION['id'], $id);
        if ($duplicado) {
            Flash::set('warning', 'Já existe outro trabalhador com esse email na sua empresa.');
            header('Location: /trabalhadores');
            exit;
        }

        $trabalhador->__set('id', $id);
        $trabalhador->__set('nome', $nome);
        $trabalhador->__set('email', $email);
        $trabalhador->__set('telefone', $telefone);
        $trabalhador->__set('funcao', $funcao);
        $trabalhador->__set('salario_dia', $salarioDia);
        $trabalhador->__set('estado', $estado);
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

        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $localizacao = trim($_POST['localizacao'] ?? '');
        $dataInicio = $_POST['data_inicio'] ?? null;
        $dataFimPrevista = $_POST['data_fim_prevista'] ?? null;
        $estado = $_POST['estado'] ?? 'planeado';
        $orcamento = (float)($_POST['orcamento'] ?? 0);

        if ($nome === '') {
            Flash::set('warning', 'O nome do projeto é obrigatório.');
            header('Location: /projetos');
            exit;
        }

        if ($orcamento <= 0) {
            Flash::set('warning', 'O orçamento tem de ser superior a 0.');
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');

        $projeto->__set('nome', $nome);
        $projeto->__set('descricao', $descricao);
        $projeto->__set('localizacao', $localizacao);
        $projeto->__set('data_inicio', $dataInicio);
        $projeto->__set('data_fim_prevista', $dataFimPrevista);
        $projeto->__set('estado', $estado);
        $projeto->__set('orcamento', $orcamento);
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
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $localizacao = trim($_POST['localizacao'] ?? '');
        $dataInicio = $_POST['data_inicio'] ?? null;
        $dataFimPrevista = $_POST['data_fim_prevista'] ?? null;
        $estado = $_POST['estado'] ?? 'planeado';
        $orcamento = (float)($_POST['orcamento'] ?? 0);

        if (!$id) {
            Flash::set('warning', 'Projeto inválido.');
            header('Location: /projetos');
            exit;
        }

        if ($nome === '') {
            Flash::set('warning', 'O nome do projeto é obrigatório.');
            header('Location: /projetos');
            exit;
        }

        if ($orcamento <= 0) {
            Flash::set('warning', 'O orçamento tem de ser superior a 0.');
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
        $projeto->__set('nome', $nome);
        $projeto->__set('descricao', $descricao);
        $projeto->__set('localizacao', $localizacao);
        $projeto->__set('data_inicio', $dataInicio);
        $projeto->__set('data_fim_prevista', $dataFimPrevista);
        $projeto->__set('estado', $estado);
        $projeto->__set('orcamento', $orcamento);

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

    private function exportarCsv($nomeFicheiro, $cabecalhos, $linhas)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomeFicheiro . '"');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, $cabecalhos, ';');

        foreach ($linhas as $linha) {
            fputcsv($output, $linha, ';');
        }

        fclose($output);
        exit;
    }

    public function relatorios()
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

        $projetosEmExecucao = 0;
        $projetosConcluidos = 0;
        $orcamentoTotal = 0;
        $trabalhadoresAtivos = 0;
        $recursosBaixoStock = 0;
        $recursosEsgotados = 0;

        foreach ($projetos as $p) {
            if (($p['estado'] ?? '') === 'em_execucao') {
                $projetosEmExecucao++;
            }

            if (($p['estado'] ?? '') === 'concluido') {
                $projetosConcluidos++;
            }

            $orcamentoTotal += (float)($p['orcamento'] ?? 0);
        }

        foreach ($trabalhadores as $t) {
            if (($t['estado'] ?? '') === 'ativo') {
                $trabalhadoresAtivos++;
            }
        }

        foreach ($recursos as $r) {
            $quantidade = (int)($r['quantidade'] ?? 0);

            if ($quantidade === 0) {
                $recursosEsgotados++;
            } elseif ($quantidade <= 10) {
                $recursosBaixoStock++;
            }
        }

        $this->view->relatorio = [
            'total_projetos' => count($projetos),
            'projetos_em_execucao' => $projetosEmExecucao,
            'projetos_concluidos' => $projetosConcluidos,
            'orcamento_total' => $orcamentoTotal,
            'total_trabalhadores' => count($trabalhadores),
            'trabalhadores_ativos' => $trabalhadoresAtivos,
            'total_equipas' => count($equipas),
            'total_recursos' => count($recursos),
            'recursos_baixo_stock' => $recursosBaixoStock,
            'recursos_esgotados' => $recursosEsgotados
        ];

        $this->render('relatorios', 'layout_dashboard');
    }

    public function exportarProjetosCsv()
    {
        $this->validarAutenticacao();

        $projeto = Container::getModel('Projeto');
        $projetos = $projeto->listarPorUtilizador($_SESSION['id']);

        $linhas = [];

        foreach ($projetos as $p) {
            $linhas[] = [
                $p['id'] ?? '',
                $p['nome'] ?? '',
                $p['estado'] ?? '',
                $p['localizacao'] ?? '',
                $p['data_inicio'] ?? '',
                $p['data_fim_prevista'] ?? '',
                $p['orcamento'] ?? '',
                $p['gestor_nome'] ?? ''
            ];
        }

        $this->exportarCsv(
            'projetos.csv',
            ['ID', 'Nome', 'Estado', 'Localização', 'Data Início', 'Prazo', 'Orçamento', 'Responsável'],
            $linhas
        );
    }

    public function exportarEquipasCsv()
    {
        $this->validarAutenticacao();

        $equipa = Container::getModel('Equipa');
        $equipas = $equipa->listarPorUtilizador($_SESSION['id']);

        $linhas = [];

        foreach ($equipas as $e) {
            $membros = $equipa->listarMembros($e['id']);

            $nomesMembros = [];
            foreach ($membros as $membro) {
                $nomesMembros[] = $membro['nome'];
            }

            $linhas[] = [
                $e['id'] ?? '',
                $e['nome'] ?? '',
                $e['especialidade'] ?? '',
                $e['lider_nome'] ?? '',
                $e['lider_funcao'] ?? '',
                count($membros),
                implode(', ', $nomesMembros)
            ];
        }

        $this->exportarCsv(
            'equipas.csv',
            ['ID', 'Nome', 'Especialidade', 'Líder', 'Função do Líder', 'Nº de Membros', 'Membros'],
            $linhas
        );
    }

    public function exportarTrabalhadoresCsv()
    {
        $this->validarAutenticacao();

        $trabalhador = Container::getModel('Trabalhador');
        $trabalhadores = $trabalhador->listarPorUtilizador($_SESSION['id']);

        $linhas = [];

        foreach ($trabalhadores as $t) {
            $linhas[] = [
                $t['id'] ?? '',
                $t['nome'] ?? '',
                $t['email'] ?? '',
                $t['telefone'] ?? '',
                $t['funcao'] ?? '',
                $t['salario_dia'] ?? '',
                $t['estado'] ?? ''
            ];
        }

        $this->exportarCsv(
            'trabalhadores.csv',
            ['ID', 'Nome', 'Email', 'Telefone', 'Função', 'Salário/Dia', 'Estado'],
            $linhas
        );
    }
    
    public function exportarRecursosCsv()
    {
        $this->validarAutenticacao();

        $recurso = Container::getModel('Recurso');
        $recursos = $recurso->listarPorUtilizador($_SESSION['id']);

        $linhas = [];

        foreach ($recursos as $r) {
            $linhas[] = [
                $r['id'] ?? '',
                $r['nome'] ?? '',
                $r['tipo'] ?? '',
                $r['quantidade'] ?? '',
                $r['custo_unitario'] ?? '',
                $r['estado'] ?? ''
            ];
        }

        $this->exportarCsv(
            'recursos.csv',
            ['ID', 'Nome', 'Tipo', 'Quantidade', 'Custo Unitário', 'Estado'],
            $linhas
        );
    }
}