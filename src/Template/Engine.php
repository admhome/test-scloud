<?php

/*
 * main class
 */
namespace src\Template;

use src\Template\Block;

class Engine
{
    const COMPILED_DIRECTORY = 'Compiled';

    private $templateContent;
    protected $templatesDirectory;

    private string $dirPrefix;

    /*
        Дополнения из большого конфига
        $config['tpl_ifs']
        $config['tpl_vars']
    */
    public $tplGlobalAddons;

    protected $templateGlobalData = [
        'vars' => [],
        'ifs' => [],
        'blocks' => [],
        'switches' => [],
    ];

    public function __construct($config)
    {
        $dirForTemplateFiles = $config['tpl_dir'];

        if (!empty($config['is_absolute'])) {
            $this->dirPrefix = '' ?? $config['current_dir'];
        } else {
            $this->dirPrefix = $_SERVER['DOCUMENT_ROOT'];
        }

        if (empty($dirForTemplateFiles))
        {
            throw new \Exception('Need directory with template files');
        }
        else
        {
            $this->templatesDirectory = '/' . trim($dirForTemplateFiles, '/');

            /*
             * check existing Compiled folder and rules on it
             */
            if (!is_dir($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . self::COMPILED_DIRECTORY)) {
                if (!mkdir($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . self::COMPILED_DIRECTORY, 0777)) {
                    throw new \Exception('Cannot create template folder');
                }
            }

            return true;
        }
    }

    public function __get($var)
    {
        return $this->{$var};
    }

    public function __set($var, $value)
    {
        return $this->{$var} = $value;
    }

    /*
     * internal spec routines
     */
    private function filterString($str)
    {
        if (preg_match('/^[A-Za-z0-9_\.]+$/', $str)) {
            return $str;
        } else {
            $str = preg_replace('#[^A-Za-z0-9_.]#', '_', $str);
            $str = preg_replace('#_+#', '_', $str);

            if (preg_match('/[0-9]/', $str[0])) {
                $str = '_' . $str;
            }

            return $str;
        }
    }

    private function prepareArray($array)
    {
        array_walk($array, function ($value, $key) {
            $array[$key] = trim($value);
        });

        return implode(', ', $array);
    }

    /*
     *	single operation
     */
    public function defineVar($varName, $varValue = '')
    {
        $varName = $this->filterString($varName);

        // да, с перезаписью дублей
        if (in_array(gettype($varValue), ['array', 'object', 'resource'])) {
            $varValue = $this->prepareArray($varValue);
        }
        return $this->templateGlobalData['vars'][$varName] = $varValue;
    }

    public function defineVars($arrayOfDefines)
    {
        // When foreach first starts executing, the internal array pointer is automatically reset to the first element of the array.
        foreach ($arrayOfDefines as $key => $value) {
            $this->defineVar($key, $value);
        }

        return true;
    }

    public function defineIf($ifName, $ifCondition = false)
    {
        $ifName = $this->filterString($ifName);

        return $this->templateGlobalData['ifs'][$ifName] = !empty($ifCondition);
    }

    public function defineSwitch($switchName, $switchVariable = '')
    {
        $switchName = $this->filterString($switchName);

        return $this->templateGlobalData['switches'][$switchName]['value'] = (empty($switchVariable) ? '' : $switchVariable);
    }

    public function defineSwitchCase($switchName, $caseName, $caseCondition = '')
    {
        $switchName = $this->filterString($switchName);
        $caseName = $this->filterString($caseName);

        return $this->templateGlobalData['switches'][$switchName]['cases'][$caseName] = (empty($caseCondition) ? '' : $caseCondition);
    }

    /*
     *	block operation
     */
    public function existBlock($blockName): bool
    {
        $blockName = $this->filterString($blockName);

        if (str_contains($blockName, '.')) {
            // not first level
            $blocks = explode('.', $blockName);
            $neededBlock = end($blocks);

            return $this->existBlock($neededBlock);
        } else {
            // first level
            return isset($this->templateGlobalData['blocks'][$blockName]);
        }
    }

    /*
     * function for recursion with raw data
     * $block - is raw block data
     */
    private function defineBlockRaw($block, $blockPath, $arrayOfDefines = [])
    {
        $blockPathArray = explode('.', $blockPath);
        $currentIndex = $block->getCurrentBlockIndex();

        if (!isset($block->{'elementData'}[$currentIndex])) {
            // если нет блока - создадим
            $block->{'elementData'}[$currentIndex] = [
                'vars' => [],
                'children' => [],
            ];
        }

        // если ошибка вида 'Indirect modification of overloaded property Block::$elementВata has no effect...' - нам нужно изменить магический метод __get в классе элемента
        if ($block->{'blockName'} == $blockPathArray[count($blockPathArray)-2]) {
            $currentBlockName = $blockPathArray[count($blockPathArray)-1];

            if (isset($block->{'elementData'}[$currentIndex]['children'][$currentBlockName])) {
                return $block->{'elementData'}[$currentIndex]['children'][$currentBlockName]->defineBlockVars($arrayOfDefines);
            } else {
                return $block->{'elementData'}[$currentIndex]['children'][$currentBlockName] = new Block($currentBlockName, $arrayOfDefines);
            }
        } else {
            $searchedKey = array_search($block->{'blockName'}, $blockPathArray);
            $nextBlockName = $blockPathArray[$searchedKey + 1];

            return $this->defineBlockRaw($block->{'elementData'}[$currentIndex]['children'][$nextBlockName], $blockPath, $arrayOfDefines);
        }
    }

    public function defineBlock($blockName, $arrayOfDefines = [])
    {
        $blockName = $this->filterString($blockName);

        if (str_contains($blockName, '.')) {
            // не первый уровень - проверить уровни и назначить каждому блоку его родителя
            $blocks = explode('.', $blockName);

            if (isset($this->templateGlobalData['blocks'][$blocks[0]]) && !empty($this->templateGlobalData['blocks'][$blocks[0]])) {
                return $this->defineBlockRaw($this->templateGlobalData['blocks'][$blocks[0]], $blockName, $arrayOfDefines);
            } else {
                return false;
            }
        } else {
            // первый уровень
            return isset($this->templateGlobalData['blocks'][$blockName])
                ? $this->templateGlobalData['blocks'][$blockName]->defineBlockVars($arrayOfDefines)
                : $this->templateGlobalData['blocks'][$blockName] = new Block($blockName, $arrayOfDefines);
        }
    }

    private function defineBlockIfRaw($blockName, $blockPath, $ifName, $ifCondition = false)
    {
        $evalDataCount = count($blockPath) - 1;
        $evalData = '$this->templateGlobalData[\'blocks\'][\'' . $blockName . '\']->{\'elementData\'}';

        if (!empty($blockPath)) {
            foreach ($blockPath as $blockPathChild) {
                eval('$lastiteration = sizeof(' . $evalData . ') - 1;');
                $evalData .= '[' . $lastiteration . '][\'children\'][\'' . $blockPathChild . '\']->{\'elementData\'}';
            }
        }

        eval('$lastiteration = sizeof(' . $evalData . ') - 1;');
        $evalData .= '[' . $lastiteration . '][\'ifs\'][\'' . $ifName . '\'] = (integer)$ifCondition;';

        eval($evalData);

        return true;
    }

    public function defineBlockIf($blockName, $ifName, $ifCondition = false)
    {
        $ifPathArray = explode('.', $blockName);
        $blockName = array_shift($ifPathArray);

        return $this->defineBlockIfRaw($blockName, $ifPathArray, $ifName, $ifCondition);
    }

    // блоки превращаются в foreach, в котором всё относительно
    private static function multiBlockCallback($matches)
    {
        $matchesFirstElem = array_shift($matches);

        foreach($matches as $matchKey => $matchValue)
        {
            $matches[$matchKey] = rtrim($matchValue, '.');
        }

        return '<?php if ( isset($' . $matches[count($matches)-2] . '[\'children\'][\'' . $matches[count($matches)-1] . '\']) ) foreach ($' . $matches[count($matches)-2] . '[\'children\'][\'' . $matches[count($matches)-1] . '\']->{\'elementData\'} as $' . $matches[count($matches)-1] . '_key => $' . $matches[count($matches)-1] . ') { ?>';
    }

    // блочный if всегда внутри foreach, в котором всё относительно
    private static function multiBlockIfCallback($matches)
    {
        $matchesFirstElem = array_shift($matches);

        foreach($matches as $matchKey => $matchValue)
        {
            $matches[$matchKey] = rtrim($matchValue, '.');
        }

        return '<?php if (isset($' . $matches[count($matches)-2] . '[\'ifs\'][\'' . $matches[count($matches) - 1] . '\']) && $' . $matches[count($matches)-2] . '[\'ifs\'][\'' . $matches[count($matches) - 1] . '\'] == true) { ?>';
    }

    private static function multiBlockVarsCallback($matches)
    {
        $matchesFirstElem = array_shift($matches);

        foreach($matches as $matchKey => $matchValue)
        {
            $matches[$matchKey] = rtrim($matchValue, '.');
        }

        return '<?php echo $' . $matches[count($matches)-2] . '[\'vars\'][\'' . $matches[count($matches)-1] . '\']; ?>';
    }

    /*
     *	routines
     */
    public function compile($templateContent)
    {
        $templateContent = preg_replace('#\{([a-zA-Z0-9\-_]+)\}#is', '<?php if (isset($this->templateGlobalData[\'vars\'][\'\1\'])) echo $this->templateGlobalData[\'vars\'][\'\1\']; ?>', $templateContent);
        $templateContent = preg_replace('#<!-- IF ([a-zA-Z0-9\-_]+) -->#is', '<?php if (isset($this->templateGlobalData[\'ifs\'][\'\1\']) && $this->templateGlobalData[\'ifs\'][\'\1\']) { ?>', $templateContent);
        
        // одиночные теги
        $templateContent = preg_replace('#<!-- BLOCK ([a-z0-9\-_]+) -->#is', '<?php if ( isset($this->templateGlobalData[\'blocks\'][\'\1\']) ) foreach ($this->templateGlobalData[\'blocks\'][\'\1\']->{\'elementData\'} as \$\1_key => \$\1) { ?>', $templateContent);
        $templateContent = preg_replace('#<!-- IF ([a-z0-9\-_]+).([a-z0-9\-_]+) -->#is', '<?php if (isset($\1[\'ifs\'][\'\2\']) && $\1[\'ifs\'][\'\2\']) { ?>', $templateContent);
        $templateContent = preg_replace('#\{([a-z0-9\-_]+).([a-z0-9\-_]+)\}#is', '<?php echo \$\1[\'vars\'][\'\2\']; ?>', $templateContent);

        // мулитиблоковые теги
        $templateContent = preg_replace_callback('#<!-- BLOCK ([a-z0-9\-_]+\.)+([a-z0-9\-_]+) -->#is', 'src\Template\Engine::multiBlockCallback', $templateContent);
        $templateContent = preg_replace_callback('#<!-- IF ([a-z0-9\-_]+\.)+([a-z0-9\-_]+) -->#is', 'src\Template\Engine::multiBlockIfCallback', $templateContent);
        $templateContent = preg_replace_callback('#\{([a-z0-9\-_]+\.)+([a-zA-Z0-9\-_]+)\}#is', 'src\Template\Engine::multiBlockVarsCallback', $templateContent);

        // закрывашки и промежуточные
        $templateContent = str_replace(['<!-- ENDBLOCK -->', '<!-- ENDIF -->'], '<?php } ?>', $templateContent);
        $templateContent = str_replace('<!-- ELSE -->', '<?php } else { ?>', $templateContent);

        $templateContent = preg_replace('#<!-- INCLUDE ([a-z0-9\.\-_]+) -->#is', '<?php $this->parse(\'\1\'); ?>', $templateContent);

        // может это добавит читаемости исходного кода...
        //$templateContent = str_replace(PHP_EOL, PHP_EOL . PHP_EOL, $templateContent);

        return $templateContent;
    }

    public function parse($templateName, $returnParsedContent = false)
    {
        if (!is_file($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . self::COMPILED_DIRECTORY . DIRECTORY_SEPARATOR . $templateName)
            || !filesize($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . self::COMPILED_DIRECTORY . DIRECTORY_SEPARATOR . $templateName)
            || (
                filemtime($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . $templateName) > filemtime($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . 'Compiled' . DIRECTORY_SEPARATOR . $templateName)
            )
        ) {
            if (is_readable($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . $templateName)
                && $templateContent = file_get_contents($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . $templateName)
            ) {
                if (file_put_contents($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . self::COMPILED_DIRECTORY . DIRECTORY_SEPARATOR . $templateName, $this->compile($templateContent)) === FALSE) {
                    throw new \Exception('Unable to write to compiled template file');
                }
            } else {
                throw new \Exception('Unable to read from compiled template file');
            }
        }

        // let out compile template
        if (!$returnParsedContent) {
            include($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . self::COMPILED_DIRECTORY . DIRECTORY_SEPARATOR . $templateName);

            return true;
        } else {
            ob_start();
            include($this->dirPrefix . $this->templatesDirectory . DIRECTORY_SEPARATOR . self::COMPILED_DIRECTORY . DIRECTORY_SEPARATOR . $templateName);
            $templateContent = ob_get_contents();
            ob_end_clean();

            return $templateContent;
        }
    }

    public function refreshGlobalVars($config)
    {
        if (!empty($config['templateGlobalData'])) {
            // \AutoConf::configure_array($this->templateGlobalData, $config['templateGlobalData']);

            foreach($this->templateGlobalData as $key => $value) {
                if (!empty($config['templateGlobalData'][$key])) {
                    $this->templateGlobalData[$key] = array_merge($this->templateGlobalData[$key], $config['templateGlobalData'][$key]);
                }
            }
        }

        return true;
    }

    public function export()
    {
        return $this->templateGlobalData;
    }
}
