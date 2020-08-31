<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Group extends Preset
{
    private int $limit = 10;

    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        // @todo show more link
        $data = explode(',', $value);
        $data = array_filter($data);
        $sliced = false;
        if ($this->getLimit()) {
            if (count($data) > $this->getLimit()) {
                $data = array_slice($data, 0, $this->getLimit());
                $sliced = true;
            }
        }
        $value = htmlspecialchars(implode(', ', $data));
        if ($sliced) {
            $value .= '...';
        }
        return $value;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }
}
