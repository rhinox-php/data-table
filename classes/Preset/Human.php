<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Human extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        $data = explode(',', $value);
        array_walk($data, function (&$value) {
            $value = $this->humanise($value);
        });
        $data = array_filter($data);
        $value = htmlspecialchars(implode(', ', $data));
        return $value;
    }

    public static function humanise($value)
    {
        if ($value == 'id') {
            return 'ID';
        }
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        $value = preg_replace('/\bid\b/i', 'ID', $value);
        $value = ucwords($value);
        return $value;
    }
}
