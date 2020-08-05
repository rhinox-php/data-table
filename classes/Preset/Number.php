<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Number extends Preset
{
    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-align-right');
        $column->addClass('rhinox-data-table-number');
    }
}
