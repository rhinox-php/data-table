<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class JsonObject extends Preset
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
        natcasesort($decoded);
        if ($type == 'html') {
            return $this->wrapHtml($decoded);
        }
        foreach ($decoded as $key => $value) {
            $decoded[] = $key . ': ' . $value;
        }
        return  implode(', ', $decoded);
    }

    protected function wrapHtml(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = '<li><b>' . $key . ':</b> ' . $value . '</li>';
        }
        return '<ul class="rhinox-data-table-json-object">' . implode('', $result) . '</ul>';
    }
}
