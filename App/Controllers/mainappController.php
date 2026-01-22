<?php 

namespace App\Controllers;

#Recursos
use MF\Controller\Action;
use MF\Model\Container;


class MainappController extends Action {

    public function dashboard() { 

        $this->render('projetos', 'layout_dashboard');
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

    public function projetos() {

        $this->render('projetos', 'layout_dashboard');
    }

}


?>