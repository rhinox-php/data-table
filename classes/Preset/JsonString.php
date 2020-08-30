<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class JsonString extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        $decoded = json_decode($value);
        if (!$decoded) {
            return $value;
        }
        return json_decode($value);
    }
}
