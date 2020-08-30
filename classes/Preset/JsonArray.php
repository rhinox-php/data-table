<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

// @todo consider escaping html inside json
class JsonArray extends Preset
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
        natcasesort($decoded);
        // @todo support applying callback to values
        if ($type == 'html') {
            return $this->wrapHtml($decoded);
        }
        return implode(', ', $decoded);
    }

    protected function wrapHtml(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        return '<ul class="rhinox-data-table-json-array"><li>' . implode('</li><li>', $data) . '</li></ul>';
    }
}
