<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Enum extends Preset
{
    private array $enums;

    public function __construct(array $enums)
    {
        $this->setEnums($enums);
    }

    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        // @todo fix column filtering
        $emums = $this->getEnums();
        if (isset($emums[$value])) {
            return $emums[$value];
        }
        return $value;
    }

    public function getEnums(): array
    {
        return $this->enums;
    }

    public function setEnums(array $enums): self
    {
        $this->enums = $enums;
        return $this;
    }
}
