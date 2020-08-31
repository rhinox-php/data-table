<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class ArrayList extends Preset
{
    private $sortFunction = 'strnatcasecmp';

    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        // @todo perhaps look at compositing presets into one
        if ($value === null) {
            return null;
        }
        if (!is_array($value)) {
            $value = [$value];
        }

        // Sort the array
        $sortFunction = $this->getSortFunction();
        if ($sortFunction) {
            usort($value, $sortFunction);
        }

        // HTML format
        if ($type === 'html') {
            // @todo allow custom tags and classes
            $result = [];
            foreach ($value as $item) {
                $result[] = '<li>' . $item . '</li>';
            }
            return '<ul class="rhinox-data-table-array">' . implode('', $result) . '</ul>';
        }

        // Text format
        // @todo allow custom seperator
        return implode(', ', $value);
    }

    public function getSortFunction(): ?callable
    {
        return $this->sortFunction;
    }

    public function setSortFunction(?callable $sortFunction)
    {
        $this->sortFunction = $sortFunction;
        return $this;
    }
}
