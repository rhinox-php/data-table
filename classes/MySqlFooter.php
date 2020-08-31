<?php

namespace Rhino\DataTable;

class MySqlFooter extends Footer
{
    private string $query;
    private array $bindings;

    public function __construct(string $query, array $bindings = [])
    {
        $this->setQuery($query, $bindings);
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function setQuery(string $query, array $bindings = [])
    {
        $this->query = $query;
        $this->bindings = $bindings;
        return $this;
    }

    public function getDataTable(): MySqlDataTable
    {
        return parent::getDataTable();
    }

    public function getColumn(): MySqlColumn
    {
        return parent::getColumn();
    }
}
