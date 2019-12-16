<?php
namespace Rhino\DataTable;

class MySqlDataTable extends DataTable
{
    private $pdo;
    private $table;
    private $joins = [];
    private $groupBys = [];
    private $bindings = [];
    private $wheres = [];
    private $havings = [];

    public function __construct($pdo, $table) {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function processSource() {
        $bindings = [];
        $columns = $this->getColumns();

        // Prepare the select column query
        $selectColumns = implode(','.PHP_EOL, array_map(function ($column) {
            $as = preg_replace('/[^a-z0-9_]/i', '', $column->getAs());
            return $column->getQuery() . ' AS `' . $as . '`';
        }, $columns));

        // Prepare the having search query
        $having = '';
        if ($this->getSearch()) {
            $havingColumns = implode('OR'.PHP_EOL, array_filter(array_map(function ($column) {
                return $column->getHaving() ? $column->getAs().' LIKE :searchGlobal ' : null;
            }, $columns)));
            $having = "HAVING ($havingColumns)";
            $bindings[':searchGlobal'] = '%'.$this->getSearch().'%';
        }
        $columnHaving = [];
        foreach ($this->getInputColumns() as $i => $inputColumn) {
            if (isset($inputColumn['search']['value']) && $inputColumn['search']['value'] && isset($columns[$i]) && $columns[$i]->getQuery()) {
                if ($inputColumn['search']['value'] === '*') {
                    $columnHaving[] = '(' . $columns[$i]->getQuery() . ' IS NOT NULL AND ' . $columns[$i]->getQuery() . ' != "")';
                } elseif (strpos($inputColumn['search']['value'], '*') !== false) {
                    $columnHaving[] = '(' . $columns[$i]->getQuery() . ' LIKE :search' . ($i + 100) . ')';
                    $bindings[':search' . ($i + 100)] = str_replace('*', '%', $inputColumn['search']['value']);
                } else {
                    $columnHaving[] = '(' . $columns[$i]->getQuery() . ' LIKE :search' . ($i + 100) . ')';
                    $bindings[':search' . ($i + 100)] = '%'.$inputColumn['search']['value'].'%';
                }
            }
        }
        foreach ($this->havings as $customHaving) {
            $columnHaving[] = '(' . $customHaving['sql'] . ')';
            foreach ($customHaving['bindings'] as $key => $value) {
                $bindings[$key] = $value;
            }
        }
        // if (!empty($columnHaving)) {
        //     $columnHaving = implode(' AND ', $columnHaving);
        //     if ($having) {
        //         $having .= ' AND ' . $columnHaving;
        //     } else {
        //         $having = "HAVING ($columnHaving)";
        //     }
        // }

        // Where
        $wheres = [];
        foreach ($this->wheres as $where) {
            $wheres[] = '(' . $where['sql'] . ')';
            foreach ($where['bindings'] as $key => $value) {
                $bindings[$key] = $value;
            }
        }
        if (!empty($wheres)) {
            $wheres = implode(' AND ' . PHP_EOL, $wheres);
        //     $wheres = 'WHERE ' . implode(' AND ' . PHP_EOL, $wheres);
        } else {
            $wheres = '';
        }

        if (!empty($columnHaving)) {
            $columnHaving = implode(' AND ', $columnHaving);
            if ($wheres) {
                $wheres = "($wheres) AND ($columnHaving)";
            } else {
                $wheres = "($columnHaving)";
            }
        }

        if ($wheres) {
            $wheres = "WHERE $wheres";
        }

        // Joins
        $joins = [];
        foreach ($this->joins as $join) {
            $joins[] = $join;
        }
        $joins = implode(PHP_EOL, $joins);

        // Group by
        $groupBys = [];
        foreach ($this->groupBys as $groupBy) {
            $groupBys[] = 'GROUP BY ' . $groupBy;
        }
        $groupBys = implode(', ' . PHP_EOL, $groupBys);

        // Order by
        $orderBy = [];
        foreach ($this->order as $columnIndex => $direction) {
            $direction = $direction == 'desc' ? 'DESC' : 'ASC';
            if ($columns[$columnIndex]->isSortable()) {
                $orderBy[] = $columns[$columnIndex]->getOrderQuery() . ' ' . $direction;
            }
        }
        $orderBy = empty($orderBy) ? '' : ('ORDER BY ' . implode(', ', $orderBy));

        // Build the query
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
                $selectColumns
            FROM {$this->table}
            $joins
            $wheres
            $groupBys
            $having
            $orderBy
            LIMIT {$this->getLength()}
            OFFSET {$this->getStart()}
        ";

        // $this->debug($sql, array_merge($this->bindings, $bindings));

        // Execute the query
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_merge($this->bindings, $bindings));

        // Fetch the results
        $data = $statement->fetchAll(\PDO::FETCH_NUM);
        $this->setData($data);

        // Get the total results
        $statement = $this->pdo->prepare('SELECT FOUND_ROWS()');
        $statement->execute();
        $total = (int) $statement->fetchColumn(0);
        $this->setRecordsTotal($total);
        $this->setRecordsFiltered($total);
    }

    public function getTable() {
        return $this->table;
    }

    public function addColumn($name, $index = null) {
        $column = new MySqlColumn($name, $this);
        if ($index !== null) {
            array_splice($this->columns, $index, 0, $column);
        } else {
            $this->columns[] = $column;
        }
        return $column;
    }

    public function insertColumn($name, $format, $position = null) {
        return $this->spliceColumn(new MySqlColumnInsert($name, $format), $position);
    }

    public function addJoin($join) {
        $this->joins[] = $join;
    }

    public function addGroupBy($groupBy) {
        $this->groupBys[] = $groupBy;
    }

    public function addWhere($sql, array $bindings = []) {
        $this->wheres[] = [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    public function addHaving($sql, array $bindings = []) {
        $this->havings[] = [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    public function bind($sql, $bindings) {
        foreach ($bindings as $key => $value) {
            $bindKey = ':binding' . (1000 + count($this->bindings));
            $sql = str_replace($key, $bindKey, $sql);
            $this->bindings[$bindKey] = $value;
        }
        return $sql;
    }

    private function debug($sql, $bindings) {
        $sql = preg_replace_callback('/:[a-z0-9]+/', function($matches) use($bindings) {
            if (isset($bindings[$matches[0]])) {
                return "'" . addslashes($bindings[$matches[0]]) . "'";
            }
            return $matches[0];
        }, $sql);
        dump($sql, $bindings);
    }
}
