<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Prefix extends Preset
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->setPrefix($prefix);
    }

    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value === null) {
            return null;
        }
        return $this->getPrefix() . $value;
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
}
