<?php

namespace Rhino\DataTable;

class SolrDataTable extends DataTable
{
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

        $orderBy = [];
        foreach ($this->order as $columnIndex => $direction) {
            if ($columns[$columnIndex]->isSortable()) {
                $query->addSort($columns[$columnIndex]->getName(), $direction);
            }
        }

        /** @var \Solarium\QueryType\Select\Result\Document[] */
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
