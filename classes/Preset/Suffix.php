<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Suffix extends Preset
{
    private string $suffix;

    public function __construct(string $suffix)
    {
        $this->setSuffix($suffix);
    }

    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value !== null && $this->getSuffix() !== null) {
            $value = $value . $this->getSuffix();
        }
        return $value;
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }

    public function setSuffix(string $suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }
}
