<?php
namespace Rhino\DataTable;

abstract class DataTable {
    use \Rhino\Core\ModuleAccess;
    use \Rhino\Core\Renderer;

    protected $request;
    protected $response;
    protected $columns = [];
    protected $data;
    protected $result;
    protected $recordsTotal;
    protected $recordsFiltered;
    protected $start;
    protected $length;
    protected $search;
    protected $inputColumns = [];
    protected $order = [];

    public function render() {
        ob_start();
        require $this->getModule()->getRoot('/views/bootstrap.php');
        return ob_get_clean();
    }

    public function process(\Rhino\Core\Http\Request $request, \Rhino\Core\Http\Response $response) {
        $this->request = $request;
        $this->response = $response;
        if (!$request->isAjax() && $request->get('csv') === null) {
            return false;
        }
        if ($request->get('csv') === null) {
            $this->setStart($request->get('start') ? : 0);
            $this->setLength($request->get('length') ? : 10);
        } else {
            $this->setStart(0);
            $this->setLength(10000);
        }
        $this->setSearch($request->get('search')['value']);
        $this->setInputColumns($request->get('columns') ?: []);
        $orders = $request->get('order');
        if (is_array($orders)) {
            foreach ($orders as $order) {
                $this->addOrder($order['column'], $order['dir']);
            }
        }
        $this->processSource();

        if ($request->get('csv') === null) {
            return $this->sendJson($request);
        } else {
            return $this->sendCsv();
        }
    }

    protected function sendJson($request) {
        $data = $this->getData();
        $result = [];
        $columns = array_values($this->getColumns());
        foreach ($data as $r => $row) {
            $indexedRow = [];
            foreach ($columns as $c => $column) {
                $indexedRow[$column->getName()] = $row[$c];
            }
            foreach ($columns as $c => $column) {
                $result[$r][$c] = $column->format($row[$c], $indexedRow, 'html');
            }
        }
        $this->response->json([
            'draw' => (int) $request->get('draw'),
            'recordsTotal' => $this->getRecordsTotal(),
            'recordsFiltered' => $this->getRecordsFiltered(),
            'data' => $result,
        ]);
        return true;
    }

    protected function sendCsv() {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename=export.csv');
        header('Pragma: no-cache');

        $columns = array_values($this->getColumns());

        $outstream = fopen('php://output', 'w');
        $outputRow = [];
        foreach ($columns as $c => $column) {
            if ($column->isExportable()) {
                $outputRow[$c] = $column->getLabel();
            }
        }
        fputcsv($outstream, $outputRow);

        $data = $this->getData();
        $result = [];
        foreach ($data as $r => $row) {
            $outputRow = [];
            $indexedRow = [];
            foreach ($columns as $c => $column) {
                if ($column->isExportable()) {
                    $indexedRow[$column->getName()] = $row[$c];
                }
            }
            foreach ($columns as $c => $column) {
                if ($column->isExportable()) {
                    $outputRow[$c] = $column->format($row[$c], $indexedRow, 'csv');
                }
            }
            fputcsv($outstream, $outputRow);
        }
        fclose($outstream);
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

    public function getInputColumns() {
        return $this->inputColumns;
    }

    public function setInputColumns($inputColumns) {
        $this->inputColumns = $inputColumns;
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
