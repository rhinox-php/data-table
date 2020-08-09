<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Bytes extends Preset
{
    const SHORT_UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
    const LONG_UNITS = ['Bytes', 'Kilobytes', 'Megabytes', 'Gigabytes', 'Terabytes', 'Petabytes', 'Exabytes'];

    private $units = self::SHORT_UNITS;

    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value !== null) {
            // @todo column filtering
            // @todo custom precision
            $precision = 2;
            $units = $this->getUnits();
            // @todo allow 1000 instead of 1024
            $pow = floor(($value ? log($value) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $value /= pow(1024, $pow);
            $value = round($value, $precision) . ' ' . $units[$pow];
        }
        return $value;
    }

    public function getUnits(): array
    {
        return $this->units;
    }

    public function setUnits(array $units): self
    {
        $this->units = $units;
        return $this;
    }
}
