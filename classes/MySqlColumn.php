<?php

namespace Rhino\DataTable;

class MySqlColumn extends Column implements MySqlSelectColumnInterface
{
    protected $query;
    protected $having;
    protected $searchWhere;
    protected ?string $orderQuery = null;
    protected ?string $filterQuery = null;

    public function __construct(MySqlDataTable $dataTable, string $name)
    {
        parent::__construct($dataTable, $name);
        $this->setQuery($dataTable ? $dataTable->getTable() . '.' . $name : $name);
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query, array $bindings = []): self
    {
        $this->query = $this->getDataTable()->bind($query, $bindings);
        return $this;
    }

    public function getOrderQuery()
    {
        return $this->orderQuery ?: $this->getQuery();
    }

    public function setOrderQuery($orderQuery): self
    {
        $this->orderQuery = $orderQuery;
        return $this;
    }

    public function getFilterQuery(): string
    {
        return $this->filterQuery ?: $this->getQuery();
    }

    public function setFilterQuery($filterQuery): self
    {
        $this->filterQuery = $filterQuery;
        return $this;
    }

    public function getAs()
    {
        return $this->getName();
    }

    public function getDataTable(): MySqlDataTable
    {
        return $this->dataTable;
    }
}
