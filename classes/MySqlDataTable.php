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
    
    public function __construct($pdo, $table) {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function processSource() {
        $bindings = [];
        $columns = $this->getColumns();

        // Prepare the select column query
        $selectColumns = implode(','.PHP_EOL, array_map(function ($column) {
            return $column->getQuery();
        }, $columns));
        
        // Prepare the having search query
        $having = '';
        if ($this->getSearch()) {
            $havingColumns = implode('OR'.PHP_EOL, array_filter(array_map(function ($column) {
                return $column->getHaving() ? $column->getHaving().' LIKE :searchGlobal ' : null;
            }, $columns)));
            $having = "HAVING ($havingColumns)";
            $bindings[':searchGlobal'] = '%'.$this->getSearch().'%';
        }
        $columnHaving = [];
        foreach ($this->getInputColumns() as $i => $inputColumn) {
            if (isset($inputColumn['search']['value']) && $inputColumn['search']['value']) {
                if ($columns[$i]->getHaving()) {
                    $columnHaving[] = '(' . $columns[$i]->getHaving() . ' LIKE :search' . ($i + 100) . ')';
                    $bindings[':search' . ($i + 100)] = '%'.$inputColumn['search']['value'].'%';
                }
            }
        }
        if (!empty($columnHaving)) {
            $columnHaving = implode(' AND ', $columnHaving);
            if ($having) {
                $having .= ' AND ' . $columnHaving;
            } else {
                $having = "HAVING ($columnHaving)";
            }
        }

        // Where
        $wheres = [];
        foreach ($this->wheres as $where) {
            $wheres[] = '(' . $where['sql'] . ')';
            foreach ($where['bindings'] as $key => $value) {
                $bindings[$key] = $value;
            }
        }
        if (!empty($wheres)) {
            $wheres = 'WHERE ' . implode(' AND ' . PHP_EOL, $wheres);
        } else {
            $wheres = '';
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
                $orderBy[] = $columns[$columnIndex]->getQuery() . ' ' . $direction;
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

    public function addColumn($name) {
        return $this->columns[] = new MySqlColumn($name, $this);
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

    public function bind($sql, $bindings) {
        foreach ($bindings as $key => $value) {
            $bindKey = ':binding' . (1000 + count($this->bindings));
            $sql = str_replace($key, $bindKey, $sql);
            $this->bindings[$bindKey] = $value; 
        }
        return $sql;
    }
}
