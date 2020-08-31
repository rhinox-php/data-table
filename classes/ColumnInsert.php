<?php

namespace Rhino\DataTable;

class ColumnInsert extends Column
{
    public function __construct(DataTable $dataTable, string $name, callable $formatter)
    {
        parent::__construct($dataTable, $name);
        $this->addFormatter($formatter);
        $this->setExportable(false);
        $this->setSearchable(false);
        $this->setSortable(false);
    }
}
