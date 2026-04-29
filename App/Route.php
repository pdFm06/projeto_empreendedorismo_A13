<?php

namespace App;

use MF\Init\Bootstrap;

class Route extends Bootstrap {

    #Rotas disponíveis
    protected function initRoutes() {

        $routes['home'] = array(
            'route' => '/',
            'controller' => 'indexController',
            'action' => 'index'
        );

        $routes['sobre_nos'] = array(
            'route' => '/sobre_nos',
            'controller' => 'indexController',
            'action' => 'sobreNos'
        );

        $routes['registar'] = array(
            'route' => '/registar',
            'controller' => 'authController',
            'action' => 'registar'
        );

        $routes['login'] = array(
            'route' => '/login',
            'controller' => 'authController',
            'action' => 'login'
        );

        $routes['criar_conta'] = array(
            'route' => '/criar_conta',
            'controller' => 'authController',
            'action' => 'criarConta'
        );

        $routes['autenticar'] = array(
            'route' => '/autenticar',
            'controller' => 'authController',
            'action' => 'autenticar'
        );

        $routes['trocar_palavra_passe'] = array(
            'route' => '/trocar_palavra_passe',
            'controller' => 'authController',
            'action' => 'trocarPalavraPasse'
        );

        $routes['mostrar_codigo'] = array(
            'route' => '/mostrar_codigo',
            'controller' => 'authController',
            'action' => 'mostrarCodigo'
        );

        $routes['enviarToken'] = array(
            'route' => '/enviarToken',
            'controller' => 'authController',
            'action' => 'enviarToken'
        );

        $routes['reenviarToken'] = array(
            'route' => '/reenviarToken',
            'controller' => 'authController',
            'action' => 'reenviarToken'
        );

        $routes['redefinirPassword'] = array(
            'route' => '/redefinirPassword',
            'controller' => 'authController',
            'action' => 'redefinirPassword'
        );

        $routes['enviarNovaPalavraPasse'] = array(
            'route' => '/enviarNovaPalavraPasse',
            'controller' => 'authController',
            'action' => 'enviarNovaPalavraPasse'
        );

        $routes['logout'] = array(
            'route' => '/logout',
            'controller' => 'authController',
            'action' => 'logout'
        );

        ## MainApp

        $routes['dashboard'] = array(
            'route' => '/dashboard',
            'controller' => 'mainappController',
            'action' => 'dashboard'
        );

        # Equipas

        $routes['equipas'] = array(
            'route' => '/equipas',
            'controller' => 'mainappController',
            'action' => 'equipas'
        );

        $routes['criar_equipa'] = array(
            'route' => '/criar_equipa',
            'controller' => 'mainappController',
            'action' => 'criarEquipa'
        );

        $routes['editar_equipa'] = array(
            'route' => '/editar_equipa',
            'controller' => 'mainappController',
            'action' => 'editarEquipa'
        );

        $routes['eliminar_equipa'] = array(
            'route' => '/eliminar_equipa',
            'controller' => 'mainappController',
            'action' => 'eliminarEquipa'
        );

        $routes['adicionar_membro_equipa'] = array(
            'route' => '/adicionar_membro_equipa',
            'controller' => 'mainappController',
            'action' => 'adicionarMembroEquipa'
        );

        $routes['remover_membro_equipa'] = array(
            'route' => '/remover_membro_equipa',
            'controller' => 'mainappController',
            'action' => 'removerMembroEquipa'
        );

        # Recursos

        $routes['recursos'] = array(
            'route' => '/recursos',
            'controller' => 'mainappController',
            'action' => 'recursos'
        );

        $routes['criar_recurso'] = array(
            'route' => '/criar_recurso',
            'controller' => 'mainappController',
            'action' => 'criarRecurso'
        );

        $routes['editar_recurso'] = array(
            'route' => '/editar_recurso',
            'controller' => 'mainappController',
            'action' => 'editarRecurso'
        );

        $routes['eliminar_recurso'] = array(
            'route' => '/eliminar_recurso',
            'controller' => 'mainappController',
            'action' => 'eliminarRecurso'
        );

        $routes['atualizar_quantidade_recurso'] = array(
            'route' => '/atualizar_quantidade_recurso',
            'controller' => 'mainappController',
            'action' => 'atualizarQuantidadeRecurso'
        );
        

        # Trabalhadores

        $routes['trabalhadores'] = array(   
            'route' => '/trabalhadores',
            'controller' => 'mainappController',
            'action' => 'trabalhadores'
        );

        $routes['criar_trabalhador'] = array(   
            'route' => '/criar_trabalhador',
            'controller' => 'mainappController',
            'action' => 'criarTrabalhador'
        );

        $routes['editar_trabalhador'] = array(
            'route' => '/editar_trabalhador',
            'controller' => 'mainappController',
            'action' => 'editarTrabalhador'
        );

        $routes['eliminar_trabalhador'] = array(
            'route' => '/eliminar_trabalhador',
            'controller' => 'mainappController',
            'action' => 'eliminarTrabalhador'
        );

        $routes['adicionar_lesao_trabalhador'] = array(
            'route' => '/adicionar_lesao_trabalhador',
            'controller' => 'mainappController',
            'action' => 'adicionarLesaoTrabalhador'
        );

        $routes['editar_lesao_trabalhador'] = array(
            'route' => '/editar_lesao_trabalhador',
            'controller' => 'mainappController',
            'action' => 'editarLesaoTrabalhador'
        );

        $routes['eliminar_lesao_trabalhador'] = array(
            'route' => '/eliminar_lesao_trabalhador',
            'controller' => 'mainappController',
            'action' => 'eliminarLesaoTrabalhador'
        );

        #Projetos

        $routes['projetos'] = array(
            'route' => '/projetos',
            'controller' => 'mainappController',
            'action' => 'projetos'
        );

        $routes['criar_projeto'] = array(
            'route' => '/criar_projeto',
            'controller' => 'mainappController',
            'action' => 'criarProjeto'
        );

        $routes['eliminar_projeto'] = array(
            'route' => '/eliminar_projeto',
            'controller' => 'mainappController',
            'action' => 'eliminarProjeto'
        );

        $routes['editar_projeto'] = array(
            'route' => '/editar_projeto',
            'controller' => 'mainappController',
            'action' => 'editarProjeto'
        );

        $routes['adicionar_equipa_projeto'] = array(
            'route' => '/adicionar_equipa_projeto',
            'controller' => 'mainappController',
            'action' => 'adicionarEquipaProjeto'
        );

        $routes['remover_equipa_projeto'] = array(
            'route' => '/remover_equipa_projeto',
            'controller' => 'mainappController',
            'action' => 'removerEquipaProjeto'
        );

        $routes['adicionar_recurso_projeto'] = array(
            'route' => '/adicionar_recurso_projeto',
            'controller' => 'mainappController',
            'action' => 'adicionarRecursoProjeto'
        );

        $routes['remover_recurso_projeto'] = array(
            'route' => '/remover_recurso_projeto',
            'controller' => 'mainappController',
            'action' => 'removerRecursoProjeto'
        );

        # Relatórios
            
        $routes['relatorios'] = array(
            'route' => '/relatorios',
            'controller' => 'mainappController',
            'action' => 'relatorios'
        );

        $routes['exportar_projetos_csv'] = array(
            'route' => '/exportar_projetos_csv',
            'controller' => 'mainappController',
            'action' => 'exportarProjetosCsv'
        );

        $routes['exportar_equipas_csv'] = array(
            'route' => '/exportar_equipas_csv',
            'controller' => 'mainappController',
            'action' => 'exportarEquipasCsv'
        );

        $routes['exportar_trabalhadores_csv'] = array(
            'route' => '/exportar_trabalhadores_csv',
            'controller' => 'mainappController',
            'action' => 'exportarTrabalhadoresCsv'
        );

        $routes['exportar_recursos_csv'] = array(
            'route' => '/exportar_recursos_csv',
            'controller' => 'mainappController',
            'action' => 'exportarRecursosCsv'
        );

        $routes['exportar_relatorio_pdf'] = array(
            'route' => '/exportar_relatorio_pdf',
            'controller' => 'mainappController',
            'action' => 'exportarRelatorioPdf'
        );

        $routes['gerar_relatorio_ia'] = array(
            'route' => '/gerar_relatorio_ia',
            'controller' => 'mainappController',
            'action' => 'gerarRelatorioIa'
        );

        $routes['criar_tarefa'] = array(
            'route' => '/criar_tarefa',
            'controller' => 'mainappController',
            'action' => 'criarTarefa'
        );

        $routes['editar_tarefa'] = array(
            'route' => '/editar_tarefa',
            'controller' => 'mainappController',
            'action' => 'editarTarefa'
        );

        $routes['eliminar_tarefa'] = array(
            'route' => '/eliminar_tarefa',
            'controller' => 'mainappController',
            'action' => 'eliminarTarefa'
        );

        $routes['definicoes'] = array(
            'route' => '/definicoes',
            'controller' => 'mainappController',
            'action' => 'definicoes'
        );

        $routes['guardar_tema'] = array(
            'route' => '/guardar_tema',
            'controller' => 'mainappController',
            'action' => 'guardarTema'
        );

        $routes['alterar_password_conta'] = array(
            'route' => '/alterar_password_conta',
            'controller' => 'mainappController',
            'action' => 'alterarPasswordConta'
        );

        $routes['apagar_conta'] = array(
            'route' => '/apagar_conta',
            'controller' => 'mainappController',
            'action' => 'apagarConta'
        );

        $routes['verificar_mfa'] = array(
            'route' => '/verificar_mfa',
            'controller' => 'authController',
            'action' => 'verificarMfaPage'
        );

        $routes['validar_mfa'] = array(
            'route' => '/validar_mfa',
            'controller' => 'authController',
            'action' => 'validarMfa'
        );

        $routes['ativar_mfa'] = array(
            'route' => '/ativar_mfa',
            'controller' => 'mainappController',
            'action' => 'ativarMfa'
        );

        $routes['desativar_mfa'] = array(
            'route' => '/desativar_mfa',
            'controller' => 'mainappController',
            'action' => 'desativarMfa'
        );

        $routes['verificar_mfa_config'] = array(
            'route' => '/verificar_mfa_config',
            'controller' => 'mainappController',
            'action' => 'verificarMfaConfigPage'
        );

        $routes['confirmar_mfa_config'] = array(
            'route' => '/confirmar_mfa_config',
            'controller' => 'mainappController',
            'action' => 'confirmarMfaConfig'
        );

        $routes['relatorio_projeto'] = array(
            'route' => '/relatorio_projeto',
            'controller' => 'mainappController',
            'action' => 'relatorioProjeto'
        );

        $routes['gerar_relatorio_projeto_ia'] = array(
            'route' => '/gerar_relatorio_projeto_ia',
            'controller' => 'mainappController',
            'action' => 'gerarRelatorioProjetoIa'
        );

        $routes['exportar_relatorio_projeto_pdf'] = array(
            'route' => '/exportar_relatorio_projeto_pdf',
            'controller' => 'mainappController',
            'action' => 'exportarRelatorioProjetoPdf'
        );

        $routes['exportar_relatorio_ia_pdf'] = array(
            'route' => '/exportar_relatorio_ia_pdf',
            'controller' => 'mainappController',
            'action' => 'exportarRelatorioIaPdf'
        );

        $routes['editar_recurso_projeto'] = array(
            'route' => '/editar_recurso_projeto',
            'controller' => 'mainappController',
            'action' => 'editarRecursoProjeto'
        );


        $this->setRoutes($routes);
    }



}

?>