<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Date extends Preset
{
    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-number');
        $column->setFilterDateRange([
            'timeZone' => $options,
        ]);
        // @todo disable time selection
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value) {
            try {
                $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
                $value = $date->format($preset['options']['format'] ?? 'Y-m-d');
            } catch (\Exception $exception) {
                // @todo optional default value
            }
        }
        return $value;
    }
}
