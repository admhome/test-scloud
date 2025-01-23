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
        $page = $_GET['page'] ?? 0;
        $page = abs(intval($page));
        $news = $this->worker->getPage($page);
        $allPages = $this->worker->getCountPages();

        $parseData = [
            'title' => 'Новости',
            'content' => [],
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

        for ($i = 0; $i < $allPages; $i++) {
            $parseData['content']['pages'][] = [
                'IND' => $i,
                'PAGE' => $i + 1,
                'STATUS' => $page == $i
                    ? 'active'
                    : '',
            ];
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

    public function create(): void
    {
        $this->tpl->render('news_form', [
            'title' => 'Создать новость',
            'templateVars' => [
                'ID' => '',
                'NAME' => '',
                'SHORT_TEXT' => '',
                'FULL_TEXT' => '',
            ],
        ]);
    }

    public function edit($id): void
    {
        $id = $this->getId($id);
        $newsItem = $this->worker->findById($id);

        if (empty($newsItem)) {
            throw new \Exception('News not found');
        }

        $this->tpl->render('news_form', [
            'title' => 'Редактировать новость',
            'templateVars' => [
                'ID' => $newsItem->getId(),
                'NAME' => $newsItem->getName(),
                'SHORT_TEXT' => $newsItem->getShortText(),
                'FULL_TEXT' => $newsItem->getFullText(),
            ],
        ]);
    }

    public function update()
    {
        if (empty($_POST)) {
            throw new \Exception('No needed data!');
        }

        $sql = '';

        if (!empty($_POST['id'])) {
            $sql = 'UPDATE news SET `name` = :name, `short_text` = :short_text, `full_text` = :full_text WHERE `id` = :id';
        } else {
            $sql = 'INSERT INTO news (`name`, `short_text`, `full_text`) VALUES (:name, :short_text, :full_text)';
        }

        $query = $this->pdo->prepare($sql);
        $query->bindValue(':name', $_POST['name'], \PDO::PARAM_STR);
        $query->bindValue(':short_text', $_POST['short_text'], \PDO::PARAM_STR);
        $query->bindValue(':full_text', $_POST['full_text'], \PDO::PARAM_STR);

        if (!empty($_POST['id'])) {
            $query->bindValue(':id', $_POST['id'], \PDO::PARAM_INT);
            $id = abs(intval($_POST['id']));
        }

        $query->execute();

        if (empty($_POST['id'])) {
            $id = $this->pdo->lastInsertId();
        }

        header('Location: /news/view?id=' . $id);

        return false;
    }

    public function delete($id)
    {
        $id = $this->getId($id);

        $sql = 'UPDATE news SET is_deleted = 1 WHERE id = :id';
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':id', $id, \PDO::PARAM_INT);
        $query->execute();

        header('Location: /news');

        return false;
    }

    protected function getId($id)
    {
        return is_array($id) ? $id[0] : $id;
    }
}