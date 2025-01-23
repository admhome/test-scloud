<?php

namespace src\Controller;

use src\Template\Processor;

class News
{
    protected $pdo;
    protected $tpl;
    protected $worker;

    public function __construct(
        \PDO $pdo,
        Processor $tpl
    ) {
        $this->pdo = $pdo;
        $this->tpl = $tpl;

        $this->worker = new \src\StorageWorker\News($this->pdo);
    }

    public function index()
    {
        $news = $this->worker->getPage(0);

        $parseData = [
            'title' => 'Новости',
            'content' => [],
            'pages' => [
                'current' => 0,
                'total' => $this->worker->getCount(),
            ],
        ];

        if ($news) {
            foreach ($news as $kk => $vv) {
                $parseData['content']['news'][] = [
                    'ID' => $vv['id'],
                    'TITLE' => $vv['name'],
                    'SHORT_TEXT' => $vv['short_text'],
                ];
            }
        }

        return $this->tpl->render('news_index', $parseData);
    }

    public function view($id)
    {
        $id = $this->getId($id);
        $newsItem = $this->worker->findById($id);

        if (empty($newsItem)) {
            throw new \Exception('News not found');
        }

        $parseData = [
            'title' => $newsItem->getName(),
            'content' => [],
            'templateVars' => [
                'ID' => $newsItem->getId(),
                'FULL_TEXT' => nl2br($newsItem->getFullText()),
            ],
            'variables' => [],
            'pages' => [],
        ];

        return $this->tpl->render('news_view', $parseData);
    }

    public function create()
    {
        echo '<pre>' . __METHOD__ . ' was called!</pre>';
    }

    public function edit($id)
    {
        $id = $this->getId($id);

        echo '<pre>' . __METHOD__ . ' was called with id: ' . $id . '!</pre>';
    }

    public function update($id)
    {
        $id = $this->getId($id);

        echo '<pre>' . __METHOD__ . ' was called with id: ' . $id . '!</pre>';
    }

    public function delete($id)
    {
        $id = $this->getId($id);

        echo '<pre>' . __METHOD__ . ' was called with id: ' . $id . '!</pre>';
    }

    protected function getId($id)
    {
        return is_array($id) ? $id[0] : $id;
    }
}