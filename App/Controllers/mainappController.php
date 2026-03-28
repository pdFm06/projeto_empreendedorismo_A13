<?php 

namespace App\Controllers;

use MF\Controller\Action;
use MF\Model\Container;
use App\Lib\Flash;
use Dompdf\Dompdf;
use Dompdf\Options;

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

        $tarefas = [];
        try {
            $tarefa = Container::getModel('Tarefa');
            if (method_exists($tarefa, 'listarPorUtilizador')) {
                $tarefas = $tarefa->listarPorUtilizador($_SESSION['id']);
            }
        } catch (\Throwable $e) {
            $tarefas = [];
        }

        $trabalhadoresLesionadosAtualmente = 0;
        try {
            $lesao = Container::getModel('LesaoTrabalhador');

            foreach ($trabalhadores as $trabalhadorItem) {
                $lesoes = $lesao->listarPorTrabalhador($trabalhadorItem['id'], $_SESSION['id']);
                $temLesaoAtiva = false;

                foreach ($lesoes as $lesaoItem) {
                    if (($lesaoItem['estado'] ?? '') === 'ativa') {
                        $temLesaoAtiva = true;
                        break;
                    }
                }

                if ($temLesaoAtiva) {
                    $trabalhadoresLesionadosAtualmente++;
                }
            }
        } catch (\Throwable $e) {
            $trabalhadoresLesionadosAtualmente = 0;
        }

        $totalProjetos = count($projetos);
        $projetosEmExecucao = 0;
        $projetosConcluidos = 0;
        $orcamentoTotal = 0;

        $projetosPorEstado = [
            'planeado' => 0,
            'em_execucao' => 0,
            'concluido' => 0,
            'suspenso' => 0
        ];

        $topProjetosOrcamento = [];

        foreach ($projetos as $p) {
            $estado = $p['estado'] ?? '';

            if ($estado === 'em_execucao') {
                $projetosEmExecucao++;
            }

            if ($estado === 'concluido') {
                $projetosConcluidos++;
            }

            if (isset($projetosPorEstado[$estado])) {
                $projetosPorEstado[$estado]++;
            }

            $orcamento = (float)($p['orcamento'] ?? 0);
            $orcamentoTotal += $orcamento;

            $topProjetosOrcamento[] = [
                'nome' => $p['nome'] ?? 'Sem nome',
                'orcamento' => $orcamento
            ];
        }

        usort($topProjetosOrcamento, function ($a, $b) {
            return $b['orcamento'] <=> $a['orcamento'];
        });

        $topProjetosOrcamento = array_slice($topProjetosOrcamento, 0, 5);

        $totalTrabalhadores = count($trabalhadores);
        $trabalhadoresAtivos = 0;
        $trabalhadoresInativos = 0;
        $salarioTotalDiario = 0;

        foreach ($trabalhadores as $t) {
            if (($t['estado'] ?? '') === 'ativo') {
                $trabalhadoresAtivos++;
            } else {
                $trabalhadoresInativos++;
            }

            $salarioTotalDiario += (float)($t['salario_dia'] ?? 0);
        }

        $salarioMedioDiario = $totalTrabalhadores > 0 ? ($salarioTotalDiario / $totalTrabalhadores) : 0;

        $totalEquipas = count($equipas);

        $totalRecursos = count($recursos);
        $recursosBaixoStock = 0;
        $recursosEsgotados = 0;
        $recursosStockNormal = 0;

        foreach ($recursos as $r) {
            $quantidade = (int)($r['quantidade'] ?? 0);

            if ($quantidade === 0) {
                $recursosEsgotados++;
            } elseif ($quantidade <= 10) {
                $recursosBaixoStock++;
            } else {
                $recursosStockNormal++;
            }
        }

        $tarefasPorEstado = [
            'pendente' => 0,
            'em_progresso' => 0,
            'concluida' => 0
        ];

        $tarefasPorPrioridade = [
            'baixa' => 0,
            'media' => 0,
            'alta' => 0
        ];

        foreach ($tarefas as $tarefaItem) {
            $estado = $tarefaItem['estado'] ?? '';
            $prioridade = $tarefaItem['prioridade'] ?? '';

            if (isset($tarefasPorEstado[$estado])) {
                $tarefasPorEstado[$estado]++;
            }

            if (isset($tarefasPorPrioridade[$prioridade])) {
                $tarefasPorPrioridade[$prioridade]++;
            }
        }

        $temporal = [
            'mes' => [],
            'trimestre' => [],
            'semestre' => []
        ];

        $inicializarSerie = function (&$bucket, $key) {
            if (!isset($bucket[$key])) {
                $bucket[$key] = [
                    'projetos' => 0,
                    'trabalhadores' => 0,
                    'equipas' => 0,
                    'recursos' => 0,
                    'tarefas' => 0,
                    'orcamento' => 0
                ];
            }
        };

        $adicionarTemporal = function (&$temporal, $data, $entidade, $orcamento = 0) use ($inicializarSerie) {
            if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
                return;
            }

            $timestamp = strtotime($data);
            if (!$timestamp) {
                return;
            }

            $ano = date('Y', $timestamp);
            $mes = (int) date('n', $timestamp);

            $mesChave = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
            $trimestreChave = $ano . '-T' . ceil($mes / 3);
            $semestreChave = $ano . '-S' . ($mes <= 6 ? 1 : 2);

            $inicializarSerie($temporal['mes'], $mesChave);
            $inicializarSerie($temporal['trimestre'], $trimestreChave);
            $inicializarSerie($temporal['semestre'], $semestreChave);

            $temporal['mes'][$mesChave][$entidade]++;
            $temporal['trimestre'][$trimestreChave][$entidade]++;
            $temporal['semestre'][$semestreChave][$entidade]++;

            if ($entidade === 'projetos') {
                $temporal['mes'][$mesChave]['orcamento'] += $orcamento;
                $temporal['trimestre'][$trimestreChave]['orcamento'] += $orcamento;
                $temporal['semestre'][$semestreChave]['orcamento'] += $orcamento;
            }
        };

        foreach ($projetos as $p) {
            $adicionarTemporal(
                $temporal,
                $p['criado_em'] ?? ($p['data_inicio'] ?? null),
                'projetos',
                (float)($p['orcamento'] ?? 0)
            );
        }

        foreach ($trabalhadores as $t) {
            $adicionarTemporal($temporal, $t['criado_em'] ?? null, 'trabalhadores');
        }

        foreach ($equipas as $e) {
            $adicionarTemporal($temporal, $e['criado_em'] ?? null, 'equipas');
        }

        foreach ($recursos as $r) {
            $adicionarTemporal($temporal, $r['criado_em'] ?? null, 'recursos');
        }

        foreach ($tarefas as $tarefaItem) {
            $adicionarTemporal($temporal, $tarefaItem['criado_em'] ?? null, 'tarefas');
        }

        ksort($temporal['mes']);
        ksort($temporal['trimestre']);
        ksort($temporal['semestre']);

        $formatarTemporal = function ($bucket) {
            return [
                'labels' => array_keys($bucket),
                'projetos' => array_map(fn($item) => $item['projetos'], $bucket),
                'trabalhadores' => array_map(fn($item) => $item['trabalhadores'], $bucket),
                'equipas' => array_map(fn($item) => $item['equipas'], $bucket),
                'recursos' => array_map(fn($item) => $item['recursos'], $bucket),
                'tarefas' => array_map(fn($item) => $item['tarefas'], $bucket),
                'orcamento' => array_map(fn($item) => $item['orcamento'], $bucket)
            ];
        };

        $calcularMedia = function ($bucket, $campo) {
            if (empty($bucket)) {
                return 0;
            }

            $total = array_sum(array_map(fn($item) => $item[$campo] ?? 0, $bucket));
            return $total / count($bucket);
        };

        $calcularComparacaoPeriodoAnterior = function ($bucket, $campo) {
            if (count($bucket) < 2) {
                return [
                    'atual' => 0,
                    'anterior' => 0,
                    'variacao_percentual' => 0
                ];
            }

            $valores = array_values($bucket);
            $atual = (float)($valores[count($valores) - 1][$campo] ?? 0);
            $anterior = (float)($valores[count($valores) - 2][$campo] ?? 0);

            if ($anterior == 0) {
                $variacao = $atual > 0 ? 100 : 0;
            } else {
                $variacao = (($atual - $anterior) / $anterior) * 100;
            }

            return [
                'atual' => $atual,
                'anterior' => $anterior,
                'variacao_percentual' => $variacao
            ];
        };

        $medias = [
            'mes' => [
                'projetos' => $calcularMedia($temporal['mes'], 'projetos'),
                'trabalhadores' => $calcularMedia($temporal['mes'], 'trabalhadores'),
                'equipas' => $calcularMedia($temporal['mes'], 'equipas'),
                'recursos' => $calcularMedia($temporal['mes'], 'recursos'),
                'tarefas' => $calcularMedia($temporal['mes'], 'tarefas'),
                'orcamento' => $calcularMedia($temporal['mes'], 'orcamento')
            ],
            'trimestre' => [
                'projetos' => $calcularMedia($temporal['trimestre'], 'projetos'),
                'trabalhadores' => $calcularMedia($temporal['trimestre'], 'trabalhadores'),
                'equipas' => $calcularMedia($temporal['trimestre'], 'equipas'),
                'recursos' => $calcularMedia($temporal['trimestre'], 'recursos'),
                'tarefas' => $calcularMedia($temporal['trimestre'], 'tarefas'),
                'orcamento' => $calcularMedia($temporal['trimestre'], 'orcamento')
            ],
            'semestre' => [
                'projetos' => $calcularMedia($temporal['semestre'], 'projetos'),
                'trabalhadores' => $calcularMedia($temporal['semestre'], 'trabalhadores'),
                'equipas' => $calcularMedia($temporal['semestre'], 'equipas'),
                'recursos' => $calcularMedia($temporal['semestre'], 'recursos'),
                'tarefas' => $calcularMedia($temporal['semestre'], 'tarefas'),
                'orcamento' => $calcularMedia($temporal['semestre'], 'orcamento')
            ]
        ];

        $comparacoes = [
            'mes' => [
                'projetos' => $calcularComparacaoPeriodoAnterior($temporal['mes'], 'projetos'),
                'tarefas' => $calcularComparacaoPeriodoAnterior($temporal['mes'], 'tarefas'),
                'orcamento' => $calcularComparacaoPeriodoAnterior($temporal['mes'], 'orcamento')
            ],
            'trimestre' => [
                'projetos' => $calcularComparacaoPeriodoAnterior($temporal['trimestre'], 'projetos'),
                'tarefas' => $calcularComparacaoPeriodoAnterior($temporal['trimestre'], 'tarefas'),
                'orcamento' => $calcularComparacaoPeriodoAnterior($temporal['trimestre'], 'orcamento')
            ],
            'semestre' => [
                'projetos' => $calcularComparacaoPeriodoAnterior($temporal['semestre'], 'projetos'),
                'tarefas' => $calcularComparacaoPeriodoAnterior($temporal['semestre'], 'tarefas'),
                'orcamento' => $calcularComparacaoPeriodoAnterior($temporal['semestre'], 'orcamento')
            ]
        ];

        $this->view->dashboard = [
            'total_projetos' => $totalProjetos,
            'projetos_em_execucao' => $projetosEmExecucao,
            'projetos_concluidos' => $projetosConcluidos,
            'orcamento_total' => $orcamentoTotal,
            'total_trabalhadores' => $totalTrabalhadores,
            'trabalhadores_ativos' => $trabalhadoresAtivos,
            'trabalhadores_inativos' => $trabalhadoresInativos,
            'trabalhadores_lesionados_ativos' => $trabalhadoresLesionadosAtualmente,
            'salario_medio_diario' => $salarioMedioDiario,
            'total_equipas' => $totalEquipas,
            'total_recursos' => $totalRecursos,
            'recursos_baixo_stock' => $recursosBaixoStock,
            'recursos_esgotados' => $recursosEsgotados,
            'recursos_stock_normal' => $recursosStockNormal,
            'total_tarefas' => count($tarefas),
            'medias' => $medias,
            'comparacoes' => $comparacoes,

            'graficos' => [
                'projetos_por_estado' => [
                    'labels' => ['Planeado', 'Em execução', 'Concluído', 'Suspenso'],
                    'values' => [
                        $projetosPorEstado['planeado'],
                        $projetosPorEstado['em_execucao'],
                        $projetosPorEstado['concluido'],
                        $projetosPorEstado['suspenso']
                    ]
                ],
                'tarefas_por_estado' => [
                    'labels' => ['Pendente', 'Em progresso', 'Concluída'],
                    'values' => [
                        $tarefasPorEstado['pendente'],
                        $tarefasPorEstado['em_progresso'],
                        $tarefasPorEstado['concluida']
                    ]
                ],
                'tarefas_por_prioridade' => [
                    'labels' => ['Baixa', 'Média', 'Alta'],
                    'values' => [
                        $tarefasPorPrioridade['baixa'],
                        $tarefasPorPrioridade['media'],
                        $tarefasPorPrioridade['alta']
                    ]
                ],
                'recursos_por_stock' => [
                    'labels' => ['Stock normal', 'Baixo stock', 'Esgotados'],
                    'values' => [
                        $recursosStockNormal,
                        $recursosBaixoStock,
                        $recursosEsgotados
                    ]
                ],
                'top_projetos_orcamento' => [
                    'labels' => array_map(function ($item) {
                        return $item['nome'];
                    }, $topProjetosOrcamento),
                    'values' => array_map(function ($item) {
                        return $item['orcamento'];
                    }, $topProjetosOrcamento)
                ],
                'temporais' => [
                    'mes' => $formatarTemporal($temporal['mes']),
                    'trimestre' => $formatarTemporal($temporal['trimestre']),
                    'semestre' => $formatarTemporal($temporal['semestre'])
                ]
            ]
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
        $lesao = Container::getModel('LesaoTrabalhador');

        $trabalhadores = $trabalhador->listarPorUtilizador($_SESSION['id']);

        foreach ($trabalhadores as &$item) {
            $item['lesoes'] = $lesao->listarPorTrabalhador($item['id'], $_SESSION['id']);
        }

        $this->view->trabalhadores = $trabalhadores;

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

    public function adicionarLesaoTrabalhador()
    {
        $this->validarAutenticacao();

        $trabalhadorId = $_POST['trabalhador_id'] ?? null;
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $dataLesao = $_POST['data_lesao'] ?? '';
        $dataRegressoPrevista = $_POST['data_regresso_prevista'] ?? null;
        $dataRegressoReal = $_POST['data_regresso_real'] ?? null;
        $estado = $_POST['estado'] ?? 'ativa';
        $observacoes = trim($_POST['observacoes'] ?? '');

        if (!$trabalhadorId) {
            Flash::set('warning', 'Trabalhador inválido.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($titulo === '') {
            Flash::set('warning', 'O título da lesão é obrigatório.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($dataLesao === '') {
            Flash::set('warning', 'A data da lesão é obrigatória.');
            header('Location: /trabalhadores');
            exit;
        }

        $trabalhador = Container::getModel('Trabalhador');
        $trabalhadorExistente = $trabalhador->obterPorIdEUtilizador($trabalhadorId, $_SESSION['id']);

        if (!$trabalhadorExistente) {
            Flash::set('danger', 'Não tem permissão para associar lesões a este trabalhador.');
            header('Location: /trabalhadores');
            exit;
        }

        $lesao = Container::getModel('LesaoTrabalhador');
        $lesao->__set('trabalhador_id', $trabalhadorId);
        $lesao->__set('utilizador_id', $_SESSION['id']);
        $lesao->__set('titulo', $titulo);
        $lesao->__set('descricao', $descricao);
        $lesao->__set('data_lesao', $dataLesao);
        $lesao->__set('data_regresso_prevista', $dataRegressoPrevista);
        $lesao->__set('data_regresso_real', $dataRegressoReal);
        $lesao->__set('estado', $estado);
        $lesao->__set('observacoes', $observacoes);

        $lesao->criar();

        Flash::set('success', 'Lesão registada com sucesso.');
        header('Location: /trabalhadores');
        exit;
    }

    public function editarLesaoTrabalhador()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $dataLesao = $_POST['data_lesao'] ?? '';
        $dataRegressoPrevista = $_POST['data_regresso_prevista'] ?? null;
        $dataRegressoReal = $_POST['data_regresso_real'] ?? null;
        $estado = $_POST['estado'] ?? 'ativa';
        $observacoes = trim($_POST['observacoes'] ?? '');

        if (!$id) {
            Flash::set('warning', 'Lesão inválida.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($titulo === '') {
            Flash::set('warning', 'O título da lesão é obrigatório.');
            header('Location: /trabalhadores');
            exit;
        }

        if ($dataLesao === '') {
            Flash::set('warning', 'A data da lesão é obrigatória.');
            header('Location: /trabalhadores');
            exit;
        }

        $lesao = Container::getModel('LesaoTrabalhador');
        $lesaoExistente = $lesao->obterPorIdEUtilizador($id, $_SESSION['id']);

        if (!$lesaoExistente) {
            Flash::set('danger', 'Não tem permissão para editar esta lesão.');
            header('Location: /trabalhadores');
            exit;
        }

        $lesao->__set('id', $id);
        $lesao->__set('utilizador_id', $_SESSION['id']);
        $lesao->__set('titulo', $titulo);
        $lesao->__set('descricao', $descricao);
        $lesao->__set('data_lesao', $dataLesao);
        $lesao->__set('data_regresso_prevista', $dataRegressoPrevista);
        $lesao->__set('data_regresso_real', $dataRegressoReal);
        $lesao->__set('estado', $estado);
        $lesao->__set('observacoes', $observacoes);

        $lesao->editar();

        Flash::set('success', 'Lesão atualizada com sucesso.');
        header('Location: /trabalhadores');
        exit;
    }

    public function eliminarLesaoTrabalhador()
    {
        $this->validarAutenticacao();

        $id = $_GET['id'] ?? null;

        if ($id) {
            $lesao = Container::getModel('LesaoTrabalhador');
            $lesao->eliminar($id, $_SESSION['id']);
            Flash::set('success', 'Lesão eliminada com sucesso.');
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
        $tarefa = Container::getModel('Tarefa');
        $trabalhador = Container::getModel('Trabalhador');

        $projetos = $projeto->listarPorUtilizador($_SESSION['id']);

        foreach ($projetos as &$p) {
            $p['equipas'] = $projeto->listarEquipas($p['id']);
            $p['recursos'] = $recurso->listarPorProjeto($p['id']);
            $p['tarefas'] = $tarefa->listarPorProjeto($p['id'], $_SESSION['id']);
        }

        $this->view->projetos = $projetos;
        $this->view->gestores = $utilizador->listarTodos();
        $this->view->equipasDisponiveis = $equipa->listarPorUtilizador($_SESSION['id']);
        $this->view->recursosDisponiveis = $recurso->listarPorUtilizador($_SESSION['id']);
        $this->view->trabalhadores = $trabalhador->listarPorUtilizador($_SESSION['id']);

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
    public function exportarRelatorioPdf()
    {
        $this->validarAutenticacao();

        $projeto = \MF\Model\Container::getModel('Projeto');
        $trabalhador = \MF\Model\Container::getModel('Trabalhador');
        $equipa = \MF\Model\Container::getModel('Equipa');
        $recurso = \MF\Model\Container::getModel('Recurso');

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

        $dados = [
            'empresa' => $_SESSION['email'] ?? 'Utilizador',
            'total_projetos' => count($projetos),
            'projetos_em_execucao' => $projetosEmExecucao,
            'projetos_concluidos' => $projetosConcluidos,
            'orcamento_total' => $orcamentoTotal,
            'total_trabalhadores' => count($trabalhadores),
            'trabalhadores_ativos' => $trabalhadoresAtivos,
            'total_equipas' => count($equipas),
            'total_recursos' => count($recursos),
            'recursos_baixo_stock' => $recursosBaixoStock,
            'recursos_esgotados' => $recursosEsgotados,
        ];

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt">
        <head>
        <meta charset="UTF-8">
        <title>Relatório Geral</title>
        <style>
            body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.5;
            }

            h1 {
            font-size: 24px;
            margin-bottom: 4px;
            }

            .subtitle {
            color: #6b7280;
            margin-bottom: 24px;
            }

            .box {
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
            }

            .label {
            font-weight: bold;
            }

            .value {
            float: right;
            }

            .section-title {
            margin-top: 24px;
            margin-bottom: 10px;
            font-size: 18px;
            }
        </style>
        </head>
        <body>
        <h1>Relatório Geral</h1>
        <div class="subtitle">Resumo da empresa associada à conta <?= htmlspecialchars($dados['empresa']) ?></div>

        <div class="section-title">Indicadores principais</div>

        <div class="box"><span class="label">Projetos</span><span class="value"><?= $dados['total_projetos'] ?></span></div>
        <div class="box"><span class="label">Projetos em execução</span><span class="value"><?= $dados['projetos_em_execucao'] ?></span></div>
        <div class="box"><span class="label">Projetos concluídos</span><span class="value"><?= $dados['projetos_concluidos'] ?></span></div>
        <div class="box"><span class="label">Equipas</span><span class="value"><?= $dados['total_equipas'] ?></span></div>
        <div class="box"><span class="label">Trabalhadores</span><span class="value"><?= $dados['total_trabalhadores'] ?></span></div>
        <div class="box"><span class="label">Trabalhadores ativos</span><span class="value"><?= $dados['trabalhadores_ativos'] ?></span></div>
        <div class="box"><span class="label">Recursos</span><span class="value"><?= $dados['total_recursos'] ?></span></div>
        <div class="box"><span class="label">Recursos com baixo stock</span><span class="value"><?= $dados['recursos_baixo_stock'] ?></span></div>
        <div class="box"><span class="label">Recursos esgotados</span><span class="value"><?= $dados['recursos_esgotados'] ?></span></div>
        <div class="box"><span class="label">Orçamento total</span><span class="value"><?= number_format((float)$dados['orcamento_total'], 2, ',', '.') ?> €</span></div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream('relatorio_geral.pdf', ['Attachment' => true]);
        exit;
    }


    private function lerConfiguracaoOpenAI()
    {
        $config = [];

        $caminho = dirname(__DIR__, 2) . '/API_KEY.env';

        if (file_exists($caminho)) {
            $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($linhas as $linha) {
                $linha = trim($linha);

                if ($linha === '' || strpos($linha, '#') === 0) {
                    continue;
                }

                if (strpos($linha, '=') !== false) {
                    [$chave, $valor] = explode('=', $linha, 2);
                    $config[trim($chave)] = trim($valor);
                }
            }
        }

        return $config;
    }
    private function chamarOpenAI($prompt)
    {
        $config = $this->lerConfiguracaoOpenAI();

        $apiKey = $config['OPENAI_API_KEY'] ?? null;
        $model = $config['OPENAI_MODEL'] ?? 'gpt-5.4-mini';

        if (!$apiKey || trim($apiKey) === '') {
            throw new \Exception('OPENAI_API_KEY não configurada.');
        }

        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'És um assistente empresarial. Escreve relatórios executivos curtos, claros e profissionais em português de Portugal. Não inventes dados. Usa apenas os dados fornecidos.'
                        ]
                    ]
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);

        if ($response === false) {
            $erro = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Erro cURL: ' . $erro);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            $mensagem = $data['error']['message'] ?? 'Erro desconhecido na API.';
            throw new \Exception($mensagem);
        }

        if (!empty($data['output_text'])) {
            return trim($data['output_text']);
        }

        if (!empty($data['output'][0]['content'][0]['text'])) {
            return trim($data['output'][0]['content'][0]['text']);
        }

        throw new \Exception('Não foi possível ler a resposta da OpenAI.');
    }

    public function gerarRelatorioIa()
    {
        $this->validarAutenticacao();

        $projeto = \MF\Model\Container::getModel('Projeto');
        $trabalhador = \MF\Model\Container::getModel('Trabalhador');
        $equipa = \MF\Model\Container::getModel('Equipa');
        $recurso = \MF\Model\Container::getModel('Recurso');

        $projetos = $projeto->listarPorUtilizador($_SESSION['id']);
        $trabalhadores = $trabalhador->listarPorUtilizador($_SESSION['id']);
        $equipas = $equipa->listarPorUtilizador($_SESSION['id']);
        $recursos = $recurso->listarPorUtilizador($_SESSION['id']);

        $projetosEmExecucao = 0;
        $projetosConcluidos = 0;
        $orcamentoTotal = 0;
        $trabalhadoresAtivos = 0;
        $trabalhadoresInativos = 0;
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
            } else {
                $trabalhadoresInativos++;
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

        $nomesProjetos = [];
        foreach ($projetos as $p) {
            $nomesProjetos[] = ($p['nome'] ?? 'Sem nome') . ' [' . ($p['estado'] ?? 'sem estado') . ']';
        }

        $prompt = "Gera um relatório executivo curto, profissional e objetivo para a empresa associada ao utilizador autenticado.\n\n"
            . "Dados da empresa:\n"
            . "- Total de projetos: " . count($projetos) . "\n"
            . "- Projetos em execução: " . $projetosEmExecucao . "\n"
            . "- Projetos concluídos: " . $projetosConcluidos . "\n"
            . "- Orçamento total dos projetos: " . number_format((float)$orcamentoTotal, 2, '.', '') . " EUR\n"
            . "- Total de equipas: " . count($equipas) . "\n"
            . "- Total de trabalhadores: " . count($trabalhadores) . "\n"
            . "- Trabalhadores ativos: " . $trabalhadoresAtivos . "\n"
            . "- Trabalhadores inativos: " . $trabalhadoresInativos . "\n"
            . "- Total de recursos: " . count($recursos) . "\n"
            . "- Recursos com baixo stock: " . $recursosBaixoStock . "\n"
            . "- Recursos esgotados: " . $recursosEsgotados . "\n"
            . "- Projetos registados: " . (empty($nomesProjetos) ? 'Nenhum' : implode('; ', $nomesProjetos)) . "\n\n"
            . "Estrutura do relatório:\n"
            . "1. Resumo executivo\n"
            . "2. Pontos positivos\n"
            . "3. Riscos ou alertas\n"
            . "4. Prioridades recomendadas\n\n"
            . "Não inventes números nem factos que não estejam nos dados.";

        try {
            $texto = $this->chamarOpenAI($prompt);
            $_SESSION['relatorio_ia'] = $texto;
            \App\Lib\Flash::set('success', 'Relatório com IA gerado com sucesso.');
        } catch (\Exception $e) {
            \App\Lib\Flash::set('danger', 'Não foi possível gerar o relatório com IA: ' . $e->getMessage());
        }

        header('Location: /relatorios');
        exit;
    }

    public function criarTarefa()
    {
        $this->validarAutenticacao();

        $projetoId = $_POST['projeto_id'] ?? null;
        $trabalhadorId = $_POST['trabalhador_id'] ?? null;
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $estado = $_POST['estado'] ?? 'pendente';
        $prioridade = $_POST['prioridade'] ?? 'media';
        $dataLimite = $_POST['data_limite'] ?? null;

        if (!$projetoId || $titulo === '') {
            Flash::set('warning', 'O título da tarefa é obrigatório.');
            header('Location: /projetos');
            exit;
        }

        $projeto = Container::getModel('Projeto');
        $projetoExistente = $projeto->obterPorIdEUtilizador($projetoId, $_SESSION['id']);

        if (!$projetoExistente) {
            Flash::set('danger', 'Não tem permissão para adicionar tarefas a este projeto.');
            header('Location: /projetos');
            exit;
        }

        if (!empty($trabalhadorId)) {
            $trabalhador = Container::getModel('Trabalhador');
            $trabalhadorExistente = $trabalhador->obterPorIdEUtilizador($trabalhadorId, $_SESSION['id']);

            if (!$trabalhadorExistente) {
                Flash::set('warning', 'Trabalhador inválido.');
                header('Location: /projetos');
                exit;
            }
        }

        $tarefa = Container::getModel('Tarefa');
        $tarefa->__set('projeto_id', $projetoId);
        $tarefa->__set('trabalhador_id', $trabalhadorId ?: null);
        $tarefa->__set('utilizador_id', $_SESSION['id']);
        $tarefa->__set('titulo', $titulo);
        $tarefa->__set('descricao', $descricao);
        $tarefa->__set('estado', $estado);
        $tarefa->__set('prioridade', $prioridade);
        $tarefa->__set('data_limite', $dataLimite ?: null);

        $tarefa->criar();

        Flash::set('success', 'Tarefa criada com sucesso.');
        header('Location: /projetos');
        exit;
    }

    public function editarTarefa()
    {
        $this->validarAutenticacao();

        $id = $_POST['id'] ?? null;
        $trabalhadorId = $_POST['trabalhador_id'] ?? null;
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $estado = $_POST['estado'] ?? 'pendente';
        $prioridade = $_POST['prioridade'] ?? 'media';
        $dataLimite = $_POST['data_limite'] ?? null;

        if (!$id || $titulo === '') {
            Flash::set('warning', 'A tarefa é inválida.');
            header('Location: /projetos');
            exit;
        }

        $tarefa = Container::getModel('Tarefa');
        $tarefaExistente = $tarefa->obterPorIdEUtilizador($id, $_SESSION['id']);

        if (!$tarefaExistente) {
            Flash::set('danger', 'Não tem permissão para editar esta tarefa.');
            header('Location: /projetos');
            exit;
        }

        if (!empty($trabalhadorId)) {
            $trabalhador = Container::getModel('Trabalhador');
            $trabalhadorExistente = $trabalhador->obterPorIdEUtilizador($trabalhadorId, $_SESSION['id']);

            if (!$trabalhadorExistente) {
                Flash::set('warning', 'Trabalhador inválido.');
                header('Location: /projetos');
                exit;
            }
        }

        $tarefa->__set('id', $id);
        $tarefa->__set('trabalhador_id', $trabalhadorId ?: null);
        $tarefa->__set('utilizador_id', $_SESSION['id']);
        $tarefa->__set('titulo', $titulo);
        $tarefa->__set('descricao', $descricao);
        $tarefa->__set('estado', $estado);
        $tarefa->__set('prioridade', $prioridade);
        $tarefa->__set('data_limite', $dataLimite ?: null);

        $tarefa->editar();

        Flash::set('success', 'Tarefa atualizada com sucesso.');
        header('Location: /projetos');
        exit;
    }

    public function eliminarTarefa()
    {
        $this->validarAutenticacao();

        $id = $_GET['id'] ?? null;

        if ($id) {
            $tarefa = Container::getModel('Tarefa');
            $tarefa->eliminar($id, $_SESSION['id']);
            Flash::set('success', 'Tarefa eliminada com sucesso.');
        }

        header('Location: /projetos');
        exit;
    }

    public function definicoes()
    {
        $this->validarAutenticacao();

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('id', $_SESSION['id']);

        $this->view->conta = $utilizador->obterContaPorId($_SESSION['id']);
        $this->render('definicoes', 'layout_dashboard');
    }

    public function guardarTema()
    {
        $this->validarAutenticacao();

        $tema = $_POST['tema'] ?? 'light';

        if (!in_array($tema, ['light', 'dark'])) {
            Flash::set('warning', 'Tema inválido.');
            header('Location: /definicoes');
            exit;
        }

        $utilizador = Container::getModel('Utilizador');
        $utilizador->atualizarTema($_SESSION['id'], $tema);

        $_SESSION['tema'] = $tema;

        Flash::set('success', 'Tema atualizado com sucesso.');
        header('Location: /definicoes');
        exit;
    }

    public function alterarPasswordConta()
    {
        $this->validarAutenticacao();

        $passwordAtual = $_POST['password_atual'] ?? '';
        $novaPassword = $_POST['nova_password'] ?? '';
        $confirmacao = $_POST['confirmar_password'] ?? '';

        if ($novaPassword === '' || $confirmacao === '') {
            Flash::set('warning', 'Preencha todos os campos da palavra-passe.');
            header('Location: /definicoes');
            exit;
        }

        if ($novaPassword !== $confirmacao) {
            Flash::set('warning', 'A nova palavra-passe e a confirmação não coincidem.');
            header('Location: /definicoes');
            exit;
        }

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $_SESSION['email']);
        $conta = $utilizador->obterPorEmail();

        if (!$conta || !password_verify($passwordAtual, trim($conta['password']))) {
            Flash::set('danger', 'A palavra-passe atual está incorreta.');
            header('Location: /definicoes');
            exit;
        }

        $hash = password_hash($novaPassword, PASSWORD_DEFAULT);
        $utilizador->atualizarPasswordConta($_SESSION['id'], $hash);

        Flash::set('success', 'Palavra-passe alterada com sucesso.');
        header('Location: /definicoes');
        exit;
    }

    public function apagarConta()
    {
        $this->validarAutenticacao();

        $password = $_POST['password_confirmacao'] ?? '';

        $utilizador = Container::getModel('Utilizador');
        $utilizador->__set('email', $_SESSION['email']);
        $conta = $utilizador->obterPorEmail();

        if (!$conta || !password_verify($password, trim($conta['password']))) {
            Flash::set('danger', 'Palavra-passe incorreta. Não foi possível apagar a conta.');
            header('Location: /definicoes');
            exit;
        }

        $utilizador->eliminarConta($_SESSION['id']);

        session_unset();
        session_destroy();

        header('Location: /login');
        exit;
    }

    public function ativarMfa()
{
    $this->validarAutenticacao();

    $utilizador = Container::getModel('Utilizador');
    $utilizador->atualizarEstadoMfa($_SESSION['id'], 1);

    Flash::set('success', 'MFA ativada com sucesso.');
    header('Location: /definicoes');
    exit;
}

public function desativarMfa()
{
    $this->validarAutenticacao();

    $utilizador = Container::getModel('Utilizador');
    $utilizador->atualizarEstadoMfa($_SESSION['id'], 0);
    $utilizador->limparCodigoMfa($_SESSION['id']);

    Flash::set('success', 'MFA desativada com sucesso.');
    header('Location: /definicoes');
    exit;
}
}