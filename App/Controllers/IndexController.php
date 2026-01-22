<?php 

namespace App\Controllers;

#Recursos
use MF\Controller\Action;
use MF\Model\Container;

#Modelos
use App\Models\Produto;
use App\Models\Info;


class IndexController extends Action {

    public function index() { 

        $this->render('landing_page', 'layout_landingpage');
    }

    public function sobreNos() {

        $this->render('about_us', 'layout_landingpage');
    }

}


?>