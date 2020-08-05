<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

abstract class Preset
{
    public function format($value, $row, $type): string
    {
        return $value;
    }

    public function configure(Column $column): void
    {
    }
}
