<?php
namespace Rhino\DataTable;

class MySqlColumn extends Column {
    private $query;
    private $having;
    
    public function __construct($name, $table = null) {
        parent::__construct($name);
        $this->setQuery($table ? $table->getTable() . '.' . $name : $name);
        $this->setHaving($name);
    }
    
    public function getQuery() {
        return $this->query;
    }

    public function setQuery($query) {
        $this->query = $query;
        return $this;
    }
    
    public function getHaving() {
        return $this->having;
    }

    public function setHaving($having) {
        $this->having = $having;
        return $this;
    }

}
