<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

// @todo provide options to true/false/null
class Boolean extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        return $value ? 'Yes' : '-';
    }
}
