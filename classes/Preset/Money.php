<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Money extends Preset
{
    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-align-right');
        $column->addClass('rhinox-data-table-number');
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type): string
    {
        // @todo handle other currencies
        // @todo allow overriding null result
        if ($value !== null) {
            $value = '$ ' . number_format($value ?: 0, 2);
        }
        return $value;
    }
}
