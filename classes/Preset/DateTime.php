<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class DateTime extends Preset
{
    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-number');
        $column->setFilterDateRange([
            'timeZone' => $options,
        ]);
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value) {
            try {
                $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
                if (isset($preset['options']['timeZone'])) {
                    $date->setTimezone($preset['options']['timeZone']);
                }
                $value = $date->format($preset['options']['format'] ?? 'Y-m-d H:i:s');
            } catch (\Exception $exception) {
                // @todo optional default value
            }
        }
        return $value;
    }
}
