<?php

namespace App\Lib;

class Flash
{
    public static function set($tipo, $mensagem)
    {
        $_SESSION['flash'] = [
            'tipo' => $tipo,
            'mensagem' => $mensagem
        ];
    }

    public static function get()
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }
}