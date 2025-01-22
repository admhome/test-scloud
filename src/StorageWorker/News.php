<?php

namespace src\StorageWorker;

use src\Model\News as NewsModel;

class News
{
    protected $connection;

    private $identityMap;
    private $unit;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $this->identityMap = [];
    }

    public function findById(int $id): NewsModel
    {
        if (!empty($this->identityMap[$id])) {
            return $this->identityMap[$id];
        }

        $sql = 'SELECT * FROM news WHERE id = :id';
        $query = $this->connection->prepare($sql);
        $query->bindValue(':id', $id, \PDO::PARAM_INT);
        $query->execute();

        if (false === $unit = $query->fetch()) {
            throw new \Exception('News unit #'.$id.' not found');
        }

        if (!empty($unit['is_deleted'])) {
            throw new \Exception('News unit was deleted');
        }

        $newsUnit = new NewsModel($unit['id']);
        $newsUnit->setName($unit['name'] ?? '');
        $newsUnit->setShortText($unit['short_text'] ?? '');
        $newsUnit->setFullText($unit['full_text'] ?? '');

        $this->identityMap[$unit['id']] = $newsUnit;

        return $newsUnit;
    }

    public function store(NewsModel $newsModel): mixed
    {
        if (0 !== $id = $newsModel->getId()) {
            $sql = 'INSERT INTO news (name, short_text, full_text) VALUES (:name, :short_text, :full_text)';
            $action = 'create';
        } else {
            $sql = 'UPDATE news SET name = :name, short_text = :short_text, full_text = :full_text WHERE id = :id';
            $action = 'update';
        }

        $query = $this->connection->prepare($sql);

        if ('update' === $action) {
            $query->bindValue(':id', $newsModel->getId(), \PDO::PARAM_INT);
        }

        $query->bindValue(':name', $newsModel->getName(), \PDO::PARAM_STR);
        $query->bindValue(':short_text', $newsModel->getShortText(), \PDO::PARAM_STR);
        $query->bindValue(':full_text', $newsModel->getFullText(), \PDO::PARAM_STR);
        $query->execute();

        if ('create' === $action) {
            $id = $this->connection->lastInsertId();
        }

        $this->identityMap[$id] = $newsModel;

        return $id;
    }

    public function deleteById(int $id): void
    {
        unset($this->identityMap[$id]);

        $sql = 'UPDATE news SET is_deleted = 1 WHERE id = :id';
        $query = $this->connection->prepare($sql);
        $query->bindValue(':id', $id, \PDO::PARAM_INT);
        $query->execute();
    }
}