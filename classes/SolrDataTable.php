<?php

namespace Rhino\DataTable;

class SolrDataTable extends DataTable
{
    const SOLR_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    private $solarium;
    private $bindings = [];

    public function __construct(\Solarium\Client $solarium)
    {
        $this->solarium = $solarium;
    }

    public function processSource()
    {
        $bindings = [];
        $columns = $this->getColumns();

        /** @var \Solarium\QueryType\Select\Query\Query */
        $query = $this->solarium->createQuery($this->solarium::QUERY_SELECT);
        $fields = [];
        foreach ($columns as $column) {
            $fields[] = $column->getName();
        }
        $fields = implode(',', $fields);
        $query->setFields($fields);

        if ($this->getSearch()) {
            $query->setQuery($this->getSearch());
        }

        // @todo filtered totals?
        // @todo default copy field
        // @todo bool drop downs

        foreach ($this->getInputColumns() as $i => $inputColumn) {
            if (isset($inputColumn['search']['value']) && $inputColumn['search']['value'] && isset($columns[$i])) {
                $searchValue = $inputColumn['search']['value'];
                $column = $columns[$i];
                if ($columns[$i]->hasFilterSelect()) {
                    // Select menu filters
                    $filterSelect = $columns[$i]->getFilterSelect($searchValue);
                    if ($filterSelect) {
                        $query->createFilterQuery($columns[$i]->getName())->setQuery($filterSelect['query']);
                    }
                } elseif ($column->hasFilterDateRange()) {
                    // Date range filters
                    $from = null;
                    $to = null;
                    $filterDateRange = $columns[$i]->getFilterDateRange();
                    $timeZone = $filterDateRange['timeZone'];

                    if (preg_match('/(?<from>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}) to (?<to>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2})/', $searchValue, $matches)) {
                        $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $matches['from'], $timeZone);
                        $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $matches['to'], $timeZone);
                    } elseif (preg_match('/(?<from>[0-9]{4}-[0-9]{2}-[0-9]{2})/', $searchValue, $matches)) {
                        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $matches['from'], $timeZone);
                        $from = $from->setTime(0, 0, 0);
                        $to = $from->setTime(23, 59, 59);
                    }

                    if ($from && $to) {
                        if ($from > $to) {
                            $temp = $to;
                            $to = $from;
                            $from = $temp;
                        }
                        $from = $from->setTime($from->format('H'), $from->format('i'), 0)->setTimezone(new \DateTimeZone('UTC'))->format(static::SOLR_DATE_FORMAT);
                        $to = $to->setTime($to->format('H'), $to->format('i'), 59)->setTimezone(new \DateTimeZone('UTC'))->format(static::SOLR_DATE_FORMAT);

                        $query->createFilterQuery($column->getName())->setQuery($column->getName() . ':[' . $from . ' TO ' . $to . ']');
                    } else {
                        // Fallback if input was typed in
                        $this->filterText($column, $searchValue, $query);
                    }
                } else {
                    // Text input filters
                    $this->filterText($column, $searchValue, $query);
                }
            }
        }

        foreach ($this->order as $columnIndex => $direction) {
            if ($columns[$columnIndex]->isSortable()) {
                $query->addSort($columns[$columnIndex]->getName(), $direction);
            }
        }

        $query->setStart($this->getStart());
        $query->setRows($this->getLength());

        /** @var \Solarium\QueryType\Select\Result\Document[] */
        // d($query->getDebug());
        $results = $this->solarium->execute($query);

        $total = $results->getNumFound();

        $data = [];
        foreach ($results as $document) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $document[$column->getName()];
            }
            $data[] = $row;
        }

        $this->setData($data);
        $this->setRecordsTotal($total);
        $this->setRecordsFiltered($total);
    }

    private function filterText(SolrColumn $column, string $searchValue, \Solarium\QueryType\Select\Query\Query $query)
    {
        // Text input filters
        if (strpos($searchValue, '~') !== false) {
            // Fuzzy search
        } elseif (preg_match('/^[0-9.]+$/', $searchValue)) {
            // Exact number search
        } elseif (preg_match('/^[0-9*.]+\s*TO\s*[0-9*.]+$/i', $searchValue)) {
            // Range search
            $searchValue = '[' . strtoupper($searchValue) . ']';
        } else {
            // Wildcard search
            $searchValue = preg_replace('/[^a-z0-9]+/i', '*', $searchValue);
            $searchValue = '*' . $searchValue . '*';
        }
        $query->createFilterQuery($column->getName())->setQuery($column->getName() . ':%L1%', [
            $searchValue,
        ]);
    }

    public function addColumn($name, $index = null)
    {
        $column = new SolrColumn($name, $this);
        if ($index !== null) {
            array_splice($this->columns, $index, 0, $column);
        } else {
            $this->columns[] = $column;
        }
        return $column;
    }

    public function insertColumn($name, $format, $position = null)
    {
        return $this->spliceColumn(new SolrColumnInsert($name, $format), $position);
    }

    public function addJoin($join)
    {
        $this->joins[] = $join;
    }

    public function addGroupBy($groupBy)
    {
        $this->groupBys[] = $groupBy;
    }

    public function addWhere($sql, array $bindings = [])
    {
        $this->wheres[] = [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    public function addHaving($sql, array $bindings = [])
    {
        $this->havings[] = [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    public function bind($sql, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $bindKey = ':binding' . (1000 + count($this->bindings));
            $sql = str_replace($key, $bindKey, $sql);
            $this->bindings[$bindKey] = $value;
        }
        return $sql;
    }

    private function debug($sql, $bindings)
    {
        $sql = preg_replace_callback('/:[a-z0-9]+/', function ($matches) use ($bindings) {
            if (isset($bindings[$matches[0]])) {
                return "'" . addslashes($bindings[$matches[0]]) . "'";
            }
            return $matches[0];
        }, $sql);
        dump($sql, $bindings);
    }
}
