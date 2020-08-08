<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class JsonArrayKey extends Preset
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
                foreach (json_decode($value) as $key => $checked) {
                    if ($checked) {
                        if ($options) {
                            $key = $options($key);
                        }
                        $result[] = '<li>' . $key . '</li>';
                    }
                }
                natcasesort($result);
                return '<ul class="rhinox-data-table-json-array">' . implode('', $result) . '</ul>';
            }
        }
        return $value;
    }
}
