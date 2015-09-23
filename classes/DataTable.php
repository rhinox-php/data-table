<?php
namespace Rhino\DataTable;

abstract class DataTable {
    use \Rhino\Core\ModuleAccess;

    protected $columns = [];
    protected $data;
    protected $result;
    protected $recordsTotal;
    protected $recordsFiltered;
    protected $start;
    protected $length;
    protected $search;
    protected $order = [];

    public function render() {
        require $this->getModule()->getRoot('/views/bootstrap.php');
    }

    public function process(\Rhino\Core\Http\Request $request, \Rhino\Core\Http\Response $response) {
        if (!$request->isAjax()) {
            return false;
        }
        $this->setStart($request->get('start') ? : 0);
        $this->setLength($request->get('length') ? : 10);
        $this->setSearch($request->get('search')['value']);
        $orders = $request->get('order');
        if (is_array($orders)) {
            foreach ($orders as $order) {
                $this->addOrder($order['column'], $order['dir']);
            }
        }
        $this->processSource();

        $data = $this->getData();
        $result = [];
        $columns = array_values($this->getColumns());
        foreach ($data as $r => $row) {
            $indexedRow = [];
            foreach ($columns as $c => $column) {
                $indexedRow[$column->getName()] = $row[$c];
            }
            foreach ($columns as $c => $column) {
                $result[$r][$c] = $column->format($row[$c], $indexedRow);
            }
        }
        $response->json([
            'draw' => (int) $request->get('draw'),
            'recordsTotal' => $this->getRecordsTotal(),
            'recordsFiltered' => $this->getRecordsFiltered(),
            'data' => $result,
        ]);
        return true;
    }

    public function getColumns() {
        return $this->columns;
    }

    public function setColumns(array $columns) {
        $this->columns = $columns;
        return $this;
    }

    protected function spliceColumn($column, $offset = null) {
        if ($offset === null) {
            $this->columns[] = $column;
        } else {
            array_splice($this->columns, $offset, 0, [$column]);
        }
        return $column;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    public function getResult() {
        return $this->result;
    }

    public function setResult($result) {
        $this->result = $result;
        return $this;
    }

    public function getRecordsTotal() {
        return $this->recordsTotal;
    }

    public function setRecordsTotal($recordsTotal) {
        $this->recordsTotal = $recordsTotal;
        return $this;
    }

    public function getRecordsFiltered() {
        return $this->recordsFiltered;
    }

    public function setRecordsFiltered($recordsFiltered) {
        $this->recordsFiltered = $recordsFiltered;
        return $this;
    }

    public function getStart() {
        return $this->start;
    }

    public function setStart($start) {
        $this->start = $start;
        return $this;
    }

    public function setLength($length) {
        $this->length = $length;
        return $this;
    }

    public function getLength() {
        return $this->length;
    }

    public function getSearch() {
        return $this->search;
    }

    public function setSearch($search) {
        $this->search = $search;
        return $this;
    }

    public function getOrder() {
        return $this->order;
    }

    public function setOrder($order) {
        $this->order = $order;
        return $this;
    }

    public function addOrder($column, $direction) {
        $this->order[$column] = $direction;
        return $this;
    }

}
