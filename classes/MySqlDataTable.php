<?php

namespace Rhino\DataTable;

use Rhino\InputData\InputData;

class MySqlDataTable extends DataTable
{
    protected $bindingCount = 1000;
    private $pdo;
    private $table;
    private $joins = [];
    private $groupBys = [];
    private $bindings = [];
    private $wheres = [];
    private $havings = [];

    public function __construct($pdo, $table)
    {
        parent::__construct();
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function processSource(InputData $input)
    {
        $bindings = [];
        /** @var MySqlColumn[] */
        $columns = $this->getColumns();

        // Prepare the select column query
        $selectColumns = [];
        foreach ($columns as $i => $column) {
            if ($column instanceof MySqlSelectColumnInterface) {
                $as = preg_replace('/[^a-z0-9_]/i', '', $column->getAs());
                $selectColumns[] = $column->getQuery() . ' AS `' . $as . '`';
            } else {
                $selectColumns[] = 'NULL AS `non_select_column_' . $i . '`';
            }
        }
        $selectColumns = implode(',' . PHP_EOL, $selectColumns);

        // Prepare the having search query
        $having = '';
        if ($this->getSearch()) {
            $i = 1000;
            $havingColumns = [];
            foreach ($columns as $column) {
                if ($column instanceof MySqlSelectColumnInterface && $column->getFilterQuery()) {
                    $bindings[':searchGlobal' . $i] = '%' . $this->getSearch() . '%';
                    $havingColumns[] = $column->getFilterQuery() . ' LIKE :searchGlobal' . $i++;
                }
            }
            $havingColumns = implode(' OR ', $havingColumns);
            $having = "HAVING ($havingColumns)";
        }
        $columnHaving = [];

        // Apply column filters
        foreach ($this->getInputColumns() as $i => $inputColumn) {
            if ($inputColumn->string('search.value')) {
                $column = $columns[$i->int()];
                if ($column->hasFilterSelect()) {
                    $this->applyFilterSelect($column, $inputColumn, $columnHaving, $bindings);
                } elseif ($column->hasFilterDateRange()) {
                    $this->applyFilterDateRange($column, $inputColumn, $columnHaving, $bindings);
                } else {
                    $this->applyFilterText($column, $inputColumn, $columnHaving, $bindings);
                }
            }
        }

        // Apply URL filters
        foreach ($input->arr('filter') as $columnName => $value) {
            foreach ($columns as $i => $column) {
                if ($column->getName() == $columnName->string()) {
                    $columnHaving[] = '(' . $column->getFilterQuery() . ' LIKE :search' . ($i + 1000) . ')';
                    $bindings[':search' . ($i + 1000)] = '%' . $value->string() . '%';
                }
            }
        }

        // Merge having queries
        if (!empty($columnHaving)) {
            $columnHaving = implode(' AND ', $columnHaving);
            if ($having) {
                $having .= ' AND ' . $columnHaving;
            } else {
                $having = "HAVING ($columnHaving)";
            }
        }

        $havings = [];
        foreach ($this->havings as $extraHaving) {
            [$havingQuery, $havingBindings] = $this->replaceBindings($extraHaving['sql'], $extraHaving['bindings']);
            $havings[] = '(' . $havingQuery . ')';
            foreach ($havingBindings as $key => $value) {
                $bindings[$key] = $value;
            }
        }
        if (!empty($havings)) {
            if ($having) {
                $having = $having . ' AND (' . implode(' AND ' . PHP_EOL, $havings) . ')';
            } else {
                $having = 'HAVING ' . implode(' AND ' . PHP_EOL, $havings);
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
            $wheres = implode(' AND ' . PHP_EOL, $wheres);
        } else {
            $wheres = '';
        }

        // if (!empty($columnHaving)) {
        //     $columnHaving = implode(' AND ', $columnHaving);
        //     if ($wheres) {
        //         $wheres = "($wheres) AND ($columnHaving)";
        //     } else {
        //         $wheres = "($columnHaving)";
        //     }
        // }

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
        $order = $this->getOrder() ?: $this->getDefaultOrder() ?: [];
        foreach ($order as $columnIndex => $direction) {
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

        // o($sql, array_merge($this->bindings, $bindings));

        // Execute the query
        $time = microtime(true);
        $statement = $this->pdo->prepare($sql);
        if (!$statement) {
            throw new Exception\QueryException('Error preparing SQL query', $this->pdo->errorInfo(), $sql);
        }
        $statement->execute(array_merge($this->bindings, $bindings));

        // @todo disable debug meta data by default
        // @todo test debug meta data, enabled and disabled
        $this->setMetaValue('queryTime', microtime(true) - $time);
        $this->setMetaValue('sql', $sql);
        $this->setMetaValue('bindings', array_merge($this->bindings, $bindings));
        // $this->setMetaValue('sqlBinded', $this->replaceBindingsInSql($sql, array_merge($this->bindings, $bindings)));

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

    public function getTable(): string
    {
        return $this->table;
    }

    public function addColumn(string $name, int $index = null): MySqlColumn
    {
        return $this->spliceColumn(new MySqlColumn($this, $name), $index);
    }

    public function insertColumn(string $name, callable $format, int $index = null): MySqlColumnInsert
    {
        return $this->spliceColumn(new MySqlColumnInsert($this, $name, $format), $index);
    }

    // @todo add join with bindings
    public function addJoin(string $join): self
    {
        $this->joins[] = $join;
        return $this;
    }

    // @todo add group by with bindings
    public function addGroupBy(string $groupBy): self
    {
        $this->groupBys[] = $groupBy;
        return $this;
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

    public function bind(string $sql, array $bindings)
    {
        // @todo check if this can be combined with replaceBindings
        foreach ($bindings as $key => $value) {
            $bindKey = ':binding' . (1000 + count($this->bindings));
            $sql = str_replace($key, $bindKey, $sql);
            $this->bindings[$bindKey] = $value;
        }
        return $sql;
    }

    public function replaceBindings(string $query, array $bindings)
    {
        $result = [];
        foreach ($bindings as $key => $value) {
            $key = ':' . trim($key, ':');
            $newKey = ':binding' . ($this->bindingCount++);
            $query = str_replace($key, $newKey, $query);
            $result[$newKey] = $value;
        }
        return [
            $query,
            $result,
        ];
    }

    protected function applyFilterSelect(MySqlColumn $column, InputData $inputColumn, array &$columnHaving, array &$bindings): void
    {
        $filterSelect = $column->getFilterSelect($inputColumn->string('search.value'));
        if ($filterSelect) {
            [$selectQuery, $selectBindings] = $filterSelect;
            [$selectQuery, $selectBindings] = $this->replaceBindings($selectQuery, $selectBindings);
            $columnHaving[] = '(' . $selectQuery . ')';
            foreach ($selectBindings as $key => $value) {
                $bindings[$key] = $value;
            }
        }
    }

    protected function applyFilterDateRange(MySqlColumn $column, InputData $inputColumn, array &$columnHaving, array &$bindings): void
    {
        $from = null;
        $to = null;

        $dateRange = $inputColumn->string('search.value');
        if (preg_match('/(?<from>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}) to (?<to>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2})/', $dateRange, $matches)) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $matches['from']);
            $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $matches['to']);
            $from = $from->setTime($from->format('H'), $from->format('i'), 0);
            $to = $to->setTime($to->format('H'), $to->format('i'), 59);
        } elseif (preg_match('/(?<from>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}) to (?<to>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})/', $dateRange, $matches)) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $matches['from']);
            $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $matches['to']);
        } elseif (preg_match('/(?<from>[0-9]{4}-[0-9]{2}-[0-9]{2})/', $dateRange, $matches)) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d', $matches['from']);
            $from = $from->setTime(0, 0, 0);
            $to = $from->setTime(23, 59, 59);
        }

        if ($from && $to) {
            if ($from > $to) {
                $temp = $to;
                $to = $from;
                $from = $temp;
            }
            $betweenQuery = '(' . $column->getFilterQuery() . ' BETWEEN :from AND :to)';
            [$betweenQuery, $betweenBindings] = $this->replaceBindings($betweenQuery, [
                ':from' => $from->format('Y-m-d H:i:s'),
                ':to' => $to->format('Y-m-d H:i:s'),
            ]);
            foreach ($betweenBindings as $key => $value) {
                $bindings[$key] = $value;
            }
            $columnHaving[] = $betweenQuery;
        } else {
            $this->applyFilterText($column, $inputColumn, $columnHaving, $bindings);
        }
    }

    protected function applyFilterText(MySqlColumn $column, InputData $inputColumn, array &$columnHaving, array &$bindings): void
    {
        [$textQuery, $textBindings] = $this->replaceBindings('(' . $column->getFilterQuery() . ' LIKE :search)', [
            ':search' => '%' . $inputColumn->string('search.value') . '%',
        ]);
        $columnHaving[] = $textQuery;
        $this->mergeBindings($bindings, $textBindings);
    }

    protected function mergeBindings(array &$existingBindings, array $newBindings): void
    {
        foreach ($newBindings as $key => $value) {
            $existingBindings[$key] = $value;
        }
    }

    // private function replaceBindingsInSql($sql, $bindings)
    // {
    //     return preg_replace_callback('/:[a-z0-9]+/', function ($matches) use ($bindings) {
    //         if (isset($bindings[$matches[0]])) {
    //             return "'" . addslashes($bindings[$matches[0]]) . "'";
    //         }
    //         return $matches[0];
    //     }, $sql);
    // }

    protected function getIdHash(): array
    {
        return [$this->getTable()];
    }
}
