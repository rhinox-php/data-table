<?php

namespace Rhino\DataTable;

class Action extends Column
{
    /** @var callable */
    private $callback;

    public function __construct(DataTable $dataTable, callable $callback, string $name = 'action')
    {
        parent::__construct($dataTable, $name);
        $this->callback = $callback;
        $this->setExportable(false);
        $this->setSearchable(false);
        $this->setSortable(false);
        $this->addClass('rhinox-data-table-action');
        $this->setHeader('');
    }

    public function format($value, $row, $type)
    {
        $method = $this->getCallback();
        $result = $method($row, $type);
        if (!is_array($result)) {
            $result = [$result];
        }
        $output = [];
        foreach ($result as $r) {
            if (method_exists($r, 'render')) {
                $output[] = $r->render();
            } else {
                $output[] = $r;
            }
        }
        return implode(' ', $output);
    }

    public function getCallback()
    {
        return $this->callback;
    }
}
