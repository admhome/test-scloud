<?php

namespace src\Template;

class Block
{
    public array $elementData = [];
    public string $blockName = '';

    public function __construct(string $blockName, array $params, $parent = '', $child = '')
    {
        if (!empty($blockName)) {
            $this->blockName = $blockName;

            if (!empty($params)) {
                $this->elementData[]['vars'] = $params;
            }

            return true;
        } else {
            throw new \Exception('Block name missing');
        }
    }

    public function getCurrentBlockIndex()
    {
        $arr = array_keys($this->elementData);

        return end($arr);
    }

    public function defineBlockVars($data)
    {
        return $this->elementData[]['vars'] = $data;
    }

    public function defineBlockIf($ifName, $ifCondition = false)
    {
        if (!empty($ifName)) {
            $index = $this->getCurrentBlockIndex();

            return $this->elementData[$index]['ifs'][$ifName] = (empty($ifCondition) ? false : $ifCondition);
        } else {
            throw new \Exception('Block If name missing');
        }
    }
}