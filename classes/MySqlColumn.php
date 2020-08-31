<?php

namespace Rhino\DataTable;

class MySqlColumn extends Column implements MySqlSelectColumnInterface
{
    private $query;
    private $having;
    private $searchWhere;
    private ?string $orderQuery = null;
    private ?string $filterQuery = null;

    public function __construct(MySqlDataTable $dataTable, string $name)
    {
        parent::__construct($dataTable, $name);
        $this->setQuery($this->getDataTable()->getTable() . '.' . $name);
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query, array $bindings = [])
    {
        $this->query = $this->getDataTable()->bind($query, $bindings);
        return $this;
    }

    public function getOrderQuery()
    {
        return $this->orderQuery ?: $this->getQuery();
    }

    public function setOrderQuery($orderQuery)
    {
        $this->orderQuery = $orderQuery;
        return $this;
    }

    public function getFilterQuery(): string
    {
        return $this->filterQuery ?: $this->getQuery();
    }

    public function setFilterQuery($filterQuery)
    {
        $this->filterQuery = $filterQuery;
        return $this;
    }

    public function getAs()
    {
        return $this->getName();
    }

    // public function getFooter(): ?MySqlFooter
    // {
    //     return parent::getFooter();
    // }

    public function setFooter(Footer $footer)
    {
        $footer->setDataTable($this->getDataTable());
        $footer->setColumn($this);
        return parent::setFooter($footer);
    }

    public function getDataTable(): MySqlDataTable
    {
        return parent::getDataTable();
    }
}
