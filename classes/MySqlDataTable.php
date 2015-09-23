<?php
namespace Rhino\DataTable;

class MySqlDataTable extends DataTable
{
    private $pdo;
    private $table;
    private $bindings = [];
    
    public function __construct($pdo, $table) {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function processSource() {
        $bindings = [];

        // Prepare the select column query
        $selectColumns = implode(','.PHP_EOL, array_map(function ($column) {
            return $column->getQuery();
        }, $this->getColumns()));

        // Prepare the having search query
        $having = '';
        if ($this->getSearch()) {
            $havingColumns = implode('OR'.PHP_EOL, array_filter(array_map(function ($column) {
                return $column->getHaving() ? $column->getHaving().' LIKE :search ' : null;
            }, $this->getColumns())));
            $having = "HAVING $havingColumns";
            $bindings[':search'] = '%'.$this->getSearch().'%';
        }
        
        // Build the query
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
                $selectColumns
            FROM {$this->table}
            $having
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

    public function addColumn($name) {
        return $this->columns[] = new MySqlColumn($name);
    }

    public function insertColumn($name, $format, $position = null) {
        return $this->spliceColumn(new MySqlColumnInsert($name, $format), $position);
    }
}
