<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Html extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        // @todo format html
        return $value;
    }
}
