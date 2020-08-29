<?php

namespace Rhino\DataTable;

class Action extends Column
{
    protected $callback;
    protected $header = '';
    protected bool $searchable = false;
    protected bool $sortable = false;

    public function __construct(DataTable $dataTable, $callback, $name = 'action')
    {
        parent::__construct($dataTable, $name);
        $this->callback = $callback;
    }

    public function format($value, $row, $type)
    {
        $method = $this->callback;
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

    public function getClassName()
    {
        return parent::getClassName() . ' rhinox-data-table-action';
    }

    public function isExportable()
    {
        return false;
    }
}
