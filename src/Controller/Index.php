<?php

namespace src\Controller;

use src\Template\Processor;

class Index
{
    protected $pdo;
    protected $tpl;

    public function __construct(
        \PDO $pdo,
        Processor $tpl
    ) {
        $this->pdo = $pdo;
        $this->tpl = $tpl;
    }

    public function index(): void
    {
        $this->tpl->render('main_index', [
            'title' => 'Главная',
        ]);
    }
}