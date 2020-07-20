<?php
namespace Rhino\DataTable;

class MySqlColumn extends Column {
    private $query;
    private $having;
    private $searchWhere;
    private $orderQuery;

    public function __construct(MySqlDataTable $dataTable, string $name) {
        parent::__construct($dataTable, $name);
        $this->setQuery($dataTable ? $dataTable->getTable() . '.' . $name : $name);
        $this->setHaving($name);
    }

    public function getQuery() {
        return $this->query;
    }

    public function setQuery(string $query, array $bindings = []): self {
        $this->query = $this->dataTable->bind($query, $bindings);
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

    public function getSearchWhere() {
        return $this->searchWhere;
    }

    public function setSearchWhere($searchWhere) {
        $this->searchWhere = $searchWhere;
        return $this;
    }

}
