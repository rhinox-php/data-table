<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Enum extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value !== null) {
            // @todo fix column filtering
            $value = $preset['options'][$value] ?? $value;
        }
        return $value;
    }
}
