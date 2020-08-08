<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Array extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        // @todo perhaps look at compositing presets into one
        if ($value && is_array($value)) {
            $result = [];
            foreach ($value as $item) {
                $result[] = '<li>' . $item . '</li>';
            }
            natcasesort($result);
            return '<ul class="rhinox-data-table-json-array">' . implode('', $result) . '</ul>';
        }
        return $value;
    }
}
