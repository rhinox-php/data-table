<?php

namespace Rhino\DataTable;

class SolrColumn extends Column
{
    public function __construct($name)
    {
        parent::__construct($name);
    }

    public function setPreset($preset, $options = null): Column
    {
        switch ($preset) {
            case 'bool':
                $this->setFilterSelect([
                    'Yes' => [
                        'query' => $this->getName() . ':1',
                    ],
                    'No' => [
                        'query' => $this->getName() . ':0',
                    ],
                ]);
                break;
        }
        return parent::setPreset($preset, $options);
    }
}
