<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Truncate extends Preset
{
    private int $maxLength;

    public function __construct(int $maxLength = 100)
    {
        $this->setMaxLength($maxLength);
    }

    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        // @todo show more link
        // @todo handle HTML better
        if (strlen($value) > $this->getMaxLength()) {
            $value = htmlspecialchars(substr($value, 0, $this->getMaxLength())) . '...';
        } else {
            $value = htmlspecialchars($value);
        }
        return $value;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    public function setMaxLength(int $maxLength): self
    {
        $this->maxLength = $maxLength;
        return $this;
    }
}
