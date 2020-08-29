<?php

namespace Rhino\DataTable;

class ArrayColumn extends Column
{
    protected $callback = null;
    protected $method = null;
    protected $property = null;
    protected $index = null;
    protected $accessorType = null;

    public function processSource($row)
    {
        switch ($this->accessorType) {
            case 'callback':
                $callback = $this->getCallback();
                return $callback($row);
            case 'method':
                $method = $this->getMethod();
                return $row->$method();
            case 'property':
                $property = $this->getProperty();
                return $row->$property;
            case 'index':
                $index = $this->getIndex();
                return $row[$index];
        }
        throw new \Exception('Cannot process column: ' . $this->getName());
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;
        $this->accessorType = 'callback';
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        $this->accessorType = 'method';
        return $this;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setProperty($property)
    {
        $this->property = $property;
        $this->accessorType = 'property';
        return $this;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setIndex($index)
    {
        $this->index = $index;
        $this->accessorType = 'index';
        return $this;
    }
}
