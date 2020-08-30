<?php

namespace Rhino\DataTable;

class ColumnInsert extends Column
{
    public function __construct(DataTable $dataTable, string $name, callable $formatter)
    {
        $this->name = $name;
        $this->addFormatter($formatter);
        $this->setExportable(true);
    }

    public function isSortable()
    {
        return false;
    }

    public function isSearchable()
    {
        return false;
    }
}
