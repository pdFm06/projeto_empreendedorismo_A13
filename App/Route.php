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

        $routes['recursos'] = array(
            'route' => '/recursos',
            'controller' => 'mainappController',
            'action' => 'recursos'
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


        $this->setRoutes($routes);
    }

}

?>