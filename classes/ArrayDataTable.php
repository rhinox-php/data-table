<?php

namespace Rhino\DataTable;

class ArrayDataTable extends DataTable
{
    protected $array = [];

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function processSource(InputData $input)
    {
        $data = $this->getArray();
        $this->setRecordsTotal(count($data));

        $data = $this->filterData($data);
        $data = $this->sortData($data);
        $data = $this->spliceData($data);

        // $result = [];
        // foreach ($data as $row) {
        //     $resultRow = [];
        //     foreach ($this->getColumns() as $column) {
        //         $resultRow[] = $column->processSource($row);
        //     }
        //     $result[] = $resultRow;
        // }

        $this->setData($data);
    }

    public function filterData(array $data): array
    {
        // @todo column filtering

        $globalSearchString = $this->getSearch();
        if ($globalSearchString) {
            // Create a wild card matcher
            $match = preg_quote($globalSearchString, '/');
            $match = str_replace('\*', '.*?', $match);
            $match = str_replace('\?', '.', $match);
            $match = preg_replace('/\s+/', '.*?', $match);
            foreach ($data as $i => $row) {
                $remove = true;
                foreach ($row as $key => $value) {
                    if (preg_match("/$match/i", $value)) {
                        $remove = false;
                        break;
                    }
                }
                if ($remove) {
                    unset($data[$i]);
                }
            }
        }

        $this->setRecordsFiltered(count($data));
        return $data;
    }

    public function sortData(array $data): array
    {
        $orderBy = [];
        foreach ($this->getOrder() as $columnIndex => $direction) {
            $orderBy[] = [
                'columnIndex' => $columnIndex,
                'direction' => $direction === 'desc' ? -1 : 1,
            ];
        }

        // @todo handle overriding default sort
        if (empty($orderBy)) {
            $orderBy[] = [
                'columnIndex' => 0,
                'direction' => -1,
            ];
        }

        // @todo implement multi column sort
        usort($data, function ($a, $b) use ($orderBy) {
            foreach ($orderBy as ['columnIndex' => $columnIndex, 'direction' => $direction]) {
                $aValue = $a[$columnIndex] ?? null;
                $bValue = $b[$columnIndex] ?? null;
                // @todo handle complex variable types
                // if ($aValue instanceof DateTime) {
                //     $aValue = $aValue->format(MYSQL_FORMAT);
                // }
                // if ($bValue instanceof DateTime) {
                //     $bValue = $bValue->format(MYSQL_FORMAT);
                // }
                return strnatcasecmp((string) $aValue, (string) $bValue) * $direction;
            }
        });
        return $data;
    }

    protected function spliceData(array $data): array
    {
        // @todo test no limit length
        if ($this->getLength() == -1) {
            return $data;
        }
        return array_splice($data, $this->getStart(), $this->getLength());
    }

    public function getArray()
    {
        return $this->array;
    }

    public function setArray(array $array)
    {
        $this->array = $array;
        return $this;
    }

    public function addColumn(string $name, int $index = null): ArrayColumn
    {
        return $this->spliceColumn(new ArrayColumn($this, $name), $index);
    }
}
