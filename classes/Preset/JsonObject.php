<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class JsonObject extends Preset
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
                foreach (json_decode($value) as $key => $value) {
                    $result[] = '<li><b>' . $key . ':</b> ' . $value . '</li>';
                }
                return '<ul class="rhinox-data-table-json-object">' . implode('', $result) . '</ul>';
            }
        }
        return $value;
    }
}
