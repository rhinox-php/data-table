<?php
namespace Rhino\DataTable;

class ArrayDataTable extends DataTable
{
    protected $array = [];

    public function processSource()
    {
        $data = $this->getArray();
        $this->setRecordsTotal(count($data));

        $result = [];
        foreach ($data as $row) {
            $resultRow = [];
            foreach ($this->getColumns() as $column) {
                $resultRow[] = $column->processSource($row);
            }
            $result[] = $resultRow;
        }

        $this->setData($result);

        $this->setRecordsFiltered(count($result));
    }

    public function getArray()
    {
        return $this->array;
    }

    public function setArray($array)
    {
        $this->array = $array;

        return $this;
    }

    public function addColumn($name)
    {
        return $this->columns[] = new ArrayColumn($name);
    }
}
