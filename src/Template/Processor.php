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

        if (!empty($data['content'])) {
            $engine = new Engine($this->engineConfig);

            foreach ($data['content'] as $block => $blockData) {
                foreach ($blockData as $kk => $vv) {
                    $engine->defineBlock($block, $vv);
                }
            }

            $content = $engine->parse($template.'.tpl.html', true);
            $engine = '';
        }

        $engine = new Engine($this->engineConfig);

        $engine->defineVars([
            'TITLE' => $data['title'],
            'CONTENT' => $content,
        ]);

        $engine->parse('base.tpl.html');
    }
}
