<?php

namespace src\Controller;

class News
{
    public function __construct()
    {
        echo '[ ] '.__CLASS__.' was created';
    }

    public function index()
    {
        echo '<pre>' . __METHOD__ . ' was called!</pre>';
    }

    public function create()
    {
        echo '<pre>' . __METHOD__ . ' was called!</pre>';
    }

    public function view($id)
    {
        $id = $this->getId($id);

        echo '<pre>' . __METHOD__ . ' was called with id: ' . var_export($id, true) . '!</pre>';
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