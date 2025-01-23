<?php

namespace src\Template;

use src\Template\Engine;

class Processor
{
    protected $engineConfig = [];

    public function __construct()
    {
        $this->engineConfig['tpl_dir'] = 'Template';

        return true;
    }

    public function render($template, $data): void
    {
        $content = '';

        // локальный шаблон
        if (!empty($data['content']) || !empty($data['templateVars'])) {
            $engine = new Engine($this->engineConfig);

            if (!empty($data['content'])) {
                foreach ($data['content'] as $block => $blockData) {
                    foreach ($blockData as $kk => $vv) {
                        $engine->defineBlock($block, $vv);
                    }
                }
            }

            if (!empty($data['templateVars'])) {
                $engine->defineVars($data['templateVars']);
            }

            $content = $engine->parse($template.'.tpl.html', true);
            $engine = '';
        }

        // глобальный шаблон
        $engine = new Engine($this->engineConfig);

        $engine->defineVars([
            'TITLE' => $data['title'],
            'CONTENT' => $content,
        ]);

        if (!empty($data['variables'])) {
            $engine->defineVars($data['variables']);
        }

        $engine->parse('base.tpl.html');
    }
}
