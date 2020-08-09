<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Date extends Preset
{
    private string $timeZone = 'UTC';
    private string $format = 'Y-m-d';

    public function configure(Column $column): void
    {
        $column->addClass('rhinox-data-table-number');
        $column->setFilterDateRange([
            'timeZone' => new \DateTimeZone($this->getTimeZone()),
        ]);
        // @todo disable time selection
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            $date = $value;
        } else {
            try {
                $date = new \DateTimeImmutable($value, new \DateTimeZone($this->getTimeZone()));
            } catch (\Exception $exception) {
                // @todo optional default value
                return $value;
            }
        }
        $value = $date->format($this->getFormat());
        return $value;
    }

    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    public function setTimeZone(string $timeZone): self
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }
}
