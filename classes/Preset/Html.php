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
        // @todo allow limiting amount to show (html tag aware trim) and have a show more button
        // @todo format html
        return $value;
    }
}
