<?php

namespace Rhino\DataTable;

use Rhino\InputData\InputData;

class ArrayDataTable extends DataTable
{
    private $array = [];

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function processSource(InputData $input)
    {
        $data = $this->getArray();

        foreach ($data as $rowIndex => $row) {
            $processedRow = [];
            foreach ($this->getColumns() as $column) {
                $processedRow[] = $column->processSource($row);
            }
            $data[$rowIndex] = $processedRow;
        }

        $this->setRecordsTotal(count($data));

        $data = $this->filterData($data);
        $data = $this->sortData($data);
        $data = $this->spliceData($data);

        $this->setData($data);
    }

    protected function filterData(array $data): array
    {
        // @todo advanced column filtering

        $columns = $this->getColumns();
        foreach ($this->getInputColumns() as $i => $inputColumn) {
            $searchValue = $inputColumn->string('search.value');
            if ($inputColumn->string('search.value')) {
                $column = $columns[$i->int()];
                if (!$column->isSearchable()) {
                    continue;
                }

                $match = preg_quote($searchValue, '/');
                $match = str_replace('\*', '.*?', $match);
                $match = str_replace('\?', '.', $match);
                $match = preg_replace('/\s+/', '.*?', $match);
                foreach ($data as $rowIndex => $row) {
                    if (!preg_match("/$match/i", $row[$i->int()])) {
                        unset($data[$rowIndex]);
                    }
                }
            }
        }

        $globalSearchString = $this->getSearch();
        if ($globalSearchString) {
            // Create a wild card matcher
            $match = preg_quote($globalSearchString, '/');
            $match = str_replace('\*', '.*?', $match);
            $match = str_replace('\?', '.', $match);
            $match = preg_replace('/\s+/', '.*?', $match);
            foreach ($data as $rowIndex => $row) {
                $remove = true;
                foreach ($row as $value) {
                    if (preg_match("/$match/i", $value)) {
                        $remove = false;
                        break;
                    }
                }
                if ($remove) {
                    unset($data[$rowIndex]);
                }
            }
        }

        $this->setRecordsFiltered(count($data));
        return $data;
    }

    protected function sortData(array $data): array
    {
        // @todo test overriding default sort
        $order = $this->getOrder() ?: $this->getDefaultOrder();
        if ($order === null) {
            return $data;
        }

        $orderBy = [];
        foreach ($order as $columnIndex => $direction) {
            $orderBy[] = [
                'columnIndex' => $columnIndex,
                'direction' => $direction === 'desc' ? -1 : 1,
            ];
        }

        // @todo implement multi column sort
        $columns = $this->getColumns();
        usort($data, function ($a, $b) use ($orderBy, $columns) {
            foreach ($orderBy as ['columnIndex' => $columnIndex, 'direction' => $direction]) {
                $column = $columns[$columnIndex];
                if (!$column->isSortable()) {
                    continue;
                }

                $aValue = $a[$columnIndex] ?? '';
                $bValue = $b[$columnIndex] ?? '';
                if (is_numeric($aValue) && is_numeric($bValue)) {
                    return ($aValue <=> $bValue) * $direction;
                }
                // @todo handle complex variable types, dates, arrays, etc
                // @todo sorting numbers bigger than INT_MAX gives incorrect results
                if ($aValue instanceof \DateTimeInterface) {
                    $aValue = $aValue->format(DATE_ISO8601);
                }
                if ($bValue instanceof \DateTimeInterface) {
                    $bValue = $bValue->format(DATE_ISO8601);
                }
                if (is_scalar($aValue) && is_scalar($bValue)) {
                    return strnatcasecmp((string) $aValue, (string) $bValue) * $direction;
                }
                return strnatcasecmp(json_encode($aValue), json_encode($bValue)) * $direction;
            }
        });
        return $data;
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

    /**
     * @return ArrayColumn[]
     */
    public function getColumns(): array
    {
        return parent::getColumns();
    }

    protected function spliceData(array $data): array
    {
        // @todo test no limit length
        if ($this->getLength() == -1) {
            return $data;
        }
        return array_splice($data, $this->getStart(), $this->getLength());
    }
}
