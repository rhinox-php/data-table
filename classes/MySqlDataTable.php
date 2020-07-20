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
    protected $bindingCount = 1000;

    public function __construct($pdo, $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function processSource(InputData $input)
    {
        $bindings = [];
        $columns = $this->getColumns();

        // Prepare the select column query
        $selectColumns = implode(',' . PHP_EOL, array_map(function ($column) {
            $as = preg_replace('/[^a-z0-9_]/i', '', $column->getAs());
            return $column->getQuery() . ' AS `' . $as . '`';
        }, $columns));

        // Prepare the having search query
        $having = '';
        if ($this->getSearch()) {
            $i = 1000;
            $havingColumns = [];
            foreach ($columns as $column) {
                if ($column->getAs()) {
                    $bindings[':searchGlobal' . $i] = '%' . $this->getSearch() . '%';
                    $havingColumns[] = $column->getAs() . ' LIKE :searchGlobal' . $i++;
                }
            }
            $havingColumns = implode(' OR ', $havingColumns);
            $having = "HAVING ($havingColumns)";
        }
        $columnHaving = [];
        foreach ($this->getInputColumns() as $i => $inputColumn) {
            if ($inputColumn->string('search.value')) {
                if ($columns[$i->int()]->getAs()) {
                    if ($columns[$i->int()]->hasFilterSelect()) {
                        $filterSelect = $columns[$i->int()]->getFilterSelect($inputColumn->string('search.value'));
                        if ($filterSelect) {
                            list($selectQuery, $selectBindings) = $filterSelect;
                            list($selectQuery, $selectBindings) = $this->replaceBindings($selectQuery, $selectBindings);
                            $columnHaving[] = '(' . $selectQuery . ')';
                            foreach ($selectBindings as $key => $value) {
                                $bindings[$key] = $value;
                            }
                        }
                    } elseif ($columns[$i->int()]->hasFilterDateRange()) {
                        $from = null;
                        $to = null;

                        $dateRange = $inputColumn->string('search.value');
                        if (preg_match('/(?<from>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}) to (?<to>[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2})/', $dateRange, $matches)) {
                            $from = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $matches['from']);
                            $to = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $matches['to']);
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
                            $from = $from->setTime($from->format('H'), $from->format('i'), 0);
                            $to = $to->setTime($to->format('H'), $to->format('i'), 59);
                            $betweenQuery = '(DATE(' . $columns[$i->int()]->getAs() . ') BETWEEN :from AND :to)';
                            list($betweenQuery, $betweenBindings) = $this->replaceBindings($betweenQuery, [
                                ':from' => $from->format('Y-m-d H:i:s'),
                                ':to' => $to->format('Y-m-d H:i:s'),
                            ]);
                            foreach ($betweenBindings as $key => $value) {
                                $bindings[$key] = $value;
                            }
                            $columnHaving[] = $betweenQuery;
                        } else {
                            $columnHaving[] = '(' . $columns[$i->int()]->getAs() . ' LIKE :search' . ($i->int() + 100) . ')';
                            $bindings[':search' . ($i->int() + 100)] = '%' . $inputColumn->string('search.value') . '%';
                        }
                    } else {
                        $columnHaving[] = '(' . $columns[$i->int()]->getAs() . ' LIKE :search' . ($i->int() + 100) . ')';
                        $bindings[':search' . ($i->int() + 100)] = '%' . $inputColumn->string('search.value') . '%';
                    }
                }
            }
        }
        foreach ($input->arr('filter') as $columnName => $value) {
            foreach ($columns as $i => $column) {
                if ($column->getName() == $columnName->string()) {
                    $columnHaving[] = '(' . $column->getAs() . ' LIKE :search' . ($i + 1000) . ')';
                    $bindings[':search' . ($i + 1000)] = '%' . $value->string() . '%';
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

        $havings = [];
        foreach ($this->havings as $extraHaving) {
            list($havingQuery, $havingBindings) = $this->replaceBindings($extraHaving['sql'], $extraHaving['bindings']);
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
        $time = microtime(true);
        $statement = $this->pdo->prepare($sql);
        if (!$statement) {
            throw new Exception\QueryException('Error preparing SQL query', $this->pdo->errorInfo(), $sql);
        }
        $statement->execute(array_merge($this->bindings, $bindings));
        $this->setMetaValue('queryTime', microtime(true) - $time);
        $this->setMetaValue('sql', $sql);
        $this->setMetaValue('bindings', array_merge($this->bindings, $bindings));

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

    public function getTable()
    {
        return $this->table;
    }

    // @todo can we move this to the base class?
    public function addColumn($name, $index = null)
    {
        $column = new MySqlColumn($this, $name, $this);
        if ($index !== null) {
            array_splice($this->columns, $index, 0, $column);
        } else {
            $this->columns[] = $column;
        }
        return $column;
    }

    public function insertColumn($name, $format, $position = null)
    {
        return $this->spliceColumn(new MySqlColumnInsert($this, $name, $format), $position);
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

    public function getIdHash(): array
    {
        return [$this->getTable()];
    }

    public function replaceBindings($query, array $bindings)
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
}
