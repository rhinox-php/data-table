<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Percent extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        // @todo show more link
        // @todo handle HTML better
        if (strlen($value) > 100) {
            $value = htmlspecialchars(substr($value, 0, 100)) . '...';
        } else {
            $value = htmlspecialchars($value);
        }
        return $value;
    }
}
