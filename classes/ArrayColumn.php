<?php
namespace Rhino\DataTable;

class ArrayColumn extends Column {

    protected $callback;
    protected $method;
    protected $property;

    public function processSource($row) {
        if ($callback = $this->getCallback()) {
            return $callback($row);
        } elseif ($method = $this->getMethod()) {
            return $row->$method();
        } elseif ($property = $this->getProperty()) {
            return $row->$property;
        }
        throw new \Exception('Cannot process column: ' . $this->getName());
    }

    public function getCallback() {
        return $this->callback;
    }

    public function setCallback($callback) {
        $this->callback = $callback;
        return $this;
    }

    public function getMethod() {
        return $this->method;
    }

    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    public function getProperty() {
        return $this->property;
    }

    public function setProperty($property) {
        $this->property = $property;
        return $this;
    }

}
