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

        # MainApp

        $routes['dashboard'] = array(
            'route' => '/dashboard',
            'controller' => 'mainappController',
            'action' => 'dashboard'
        );

        $routes['equipas'] = array(
            'route' => '/equipas',
            'controller' => 'mainappController',
            'action' => 'equipas'
        );

        $routes['recursos'] = array(
            'route' => '/recursos',
            'controller' => 'mainappController',
            'action' => 'recursos'
        );

        $routes['trabalhadores'] = array(
            'route' => '/trabalhadores',
            'controller' => 'mainappController',
            'action' => 'trabalhadores'
        );

        $routes['projetos'] = array(
            'route' => '/projetos',
            'controller' => 'mainappController',
            'action' => 'projetos'
        );


        $this->setRoutes($routes);
    }

}

?>