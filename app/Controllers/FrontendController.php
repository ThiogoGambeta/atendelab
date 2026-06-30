<?php

class FrontendController
{
    public function pessoas()
    {
        require __DIR__ . '/../Views/pessoas/index.php';
    }

    public function tipos()
    {
        require __DIR__ . '/../Views/tipos-atendimentos/index.php';
    }

    public function atendimentos()
    {
        require __DIR__ . '/../Views/atendimentos/index.php';
    }
}