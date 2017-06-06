<?php
namespace Rhino\DataTable;

class MySqlColumn extends Column {
    private $table;
    private $query;
    private $having;
    private $orderQuery;

    public function __construct($name, $table = null) {
        parent::__construct($name);
        $this->table = $table;
        $this->setQuery($table ? $table->getTable() . '.' . $name : $name);
        $this->setHaving($name);
    }

    public function getQuery() {
        return $this->query;
    }

    public function setQuery($query, array $bindings = []) {
        $this->query = $this->table->bind($query, $bindings);
        return $this;
    }

    public function getOrderQuery() {
        return $this->orderQuery ?: $this->getQuery();
    }

    public function setOrderQuery($orderQuery) {
        $this->orderQuery = $orderQuery;
        return $this;
    }

    public function getAs() {
        return $this->getName();
    }

    public function getHaving() {
        return $this->having;
    }

    public function setHaving($having) {
        $this->having = $having;
        return $this;
    }

}
