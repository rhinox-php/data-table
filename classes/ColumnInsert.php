<?php
namespace Rhino\DataTable;

class ColumnInsert extends Column
{
    public function __construct($name, $format)
    {
        $this->name = $name;
        $this->format = $format;
    }

    public function isSortable() {
        return false;
    }

    public function isSearchable() {
        return false;
    }
}
