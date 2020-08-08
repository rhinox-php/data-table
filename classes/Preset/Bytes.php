<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Bytes extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value !== null) {
            // @todo fix column filtering
            $precision = 2;
            $units = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
            $pow = floor(($value ? log($value) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $value /= pow(1024, $pow);
            $value = round($value, $precision) . ' ' . $units[$pow];
        }
        return $value;
    }
}
