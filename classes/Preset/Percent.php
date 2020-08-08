<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Percent extends Preset
{
    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-align-right');
        $column->addClass('rhinox-data-table-number');
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value !== null) {
            $value = round($value) . ' %';
        }
        return $value;
    }
}
