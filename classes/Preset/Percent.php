<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Percent extends Preset
{
    private int $decimalPlaces;

    public function __construct(int $decimalPlaces = 0)
    {
        $this->setDecimalPlaces($decimalPlaces);
    }

    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-align-right');
        $column->addClass('rhinox-data-table-number');
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value !== null) {
            $value = number_format($value, $this->getDecimalPlaces()) . ' %';
        }
        return $value;
    }

    public function getDecimalPlaces(): string
    {
        return $this->decimalPlaces;
    }

    public function setDecimalPlaces(string $decimalPlaces): self
    {
        $this->decimalPlaces = $decimalPlaces;
        return $this;
    }
}
