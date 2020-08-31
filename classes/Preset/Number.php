<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Number extends Preset
{
    private int $decimalPlaces = 0;
    private string $nullValue = '';
    private string $prefix = '';
    private string $suffix = '';

    public function __construct(int $decimalPlaces = 0, string $nullValue = '')
    {
        $this->setDecimalPlaces($decimalPlaces);
        $this->setNullValue($nullValue);
    }

    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-align-right');
        $column->addClass('rhinox-data-table-number');
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value === null) {
            return $this->getNullValue();
        }
        return $this->getPrefix() . number_format($value ?: 0, $this->getDecimalPlaces()) . $this->getSuffix();
    }

    public function getDecimalPlaces(): int
    {
        return $this->decimalPlaces;
    }

    public function setDecimalPlaces(int $decimalPlaces)
    {
        $this->decimalPlaces = $decimalPlaces;
        return $this;
    }

    public function getNullValue(): string
    {
        return $this->nullValue;
    }

    public function setNullValue(string $nullValue)
    {
        $this->nullValue = $nullValue;
        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }

    public function setSuffix(string $suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }
}
