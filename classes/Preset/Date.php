<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Date extends Preset
{
    // @todo allow overriding stored timezone
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
        if ($value instanceof \DateTime) {
            $date = \DateTimeImmutable::createFromMutable($value);
        } elseif ($value instanceof \DateTimeImmutable) {
            $date = $value;
        } else {
            try {
                $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
            } catch (\Exception $exception) {
                // @todo optional default value
                return $value;
            }
        }
        $date = $date->setTimezone(new \DateTimeZone($this->getTimeZone()));
        $value = $date->format($this->getFormat());
        return $value;
    }

    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    public function setTimeZone(string $timeZone)
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format)
    {
        $this->format = $format;
        return $this;
    }
}
