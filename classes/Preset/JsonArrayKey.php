<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class JsonArrayKey extends Preset
{
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        if (!$value) {
            return $value;
        }
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $value;
        }
        // @todo support applying callback to values
        $result = [];
        foreach ($decoded as $key => $checked) {
            if (!$checked) {
                continue;
            }
            $result[] = $key;
        }
        natcasesort($result);
        if ($type == 'html') {
            return $this->wrapHtml($result);
        }
        return implode(', ', $result);
    }

    protected function wrapHtml(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        return '<ul class="rhinox-data-table-json-array"><li>' . implode('</li><li>', $data) . '</li></ul>';
    }
}
