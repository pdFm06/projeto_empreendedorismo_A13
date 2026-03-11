<?php

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    require_once "../vendor/autoload.php";

    $route = new \App\Route;
?>