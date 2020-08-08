<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class JsonArray extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($type == 'html') {
            if ($value) {
                $result = [];
                foreach (json_decode($value) as $value) {
                    $result[] = '<li>' . $value . '</li>';
                }
                return '<ul class="rhinox-data-table-json-array">' . implode('', $result) . '</ul>';
            }
        }
        return $value;
    }
}
