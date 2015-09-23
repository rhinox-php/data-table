<?php
namespace Rhino\DataTable;

class MySqlColumnInsert extends ColumnInsert
{
    public function getQuery()
    {
        return 'NULL AS '.$this->getName();
    }

    public function getHaving()
    {
        return;
    }
}
