<?php
namespace Rhino\DataTable;

class MySqlColumnInsert extends ColumnInsert
{
    public function getQuery()
    {
        return 'NULL';
    }

    public function getAs()
    {
        return $this->getName();
    }
}
