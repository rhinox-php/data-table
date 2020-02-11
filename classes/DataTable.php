<?php

namespace Rhino\DataTable;

abstract class DataTable
{
    protected $request;
    protected $response;
    protected $id;
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
    protected $defaultOrder = [
        [1, 'desc'],
    ];
    protected $saveState = true;
    protected $tableButtons = [];
    protected $rowFormatters = [];
    protected $meta = [];

    public function render()
    {
        ob_start();
        require \Rhino\DataTable\ROOT . '/views/bootstrap.php';
        return ob_get_clean();
    }

    public function createButton(array $options)
    {
        $options = new \Rhino\Core\InputData($options);
        $confirmation = '';
        if ($options->string('confirm')) {
            $confirmation = ' onclick="if (!confirm(\'' . htmlspecialchars($options->string('confirm'), ENT_QUOTES) . '\')) { event.stopImmediatePropagation(); event.preventDefault(); }"';
        }
        if ($options->bool('action')) {
            return '
                <form action="' . $options->string('action') . '" method="post">
                    <button class="btn btn-xs btn-' . $options->string('style') . '"' . $confirmation . '>' . $options->string('text') . '</button>
                </form>
            ';
        } else {
            return '<a href="' . $options->string('href') . '" class="btn btn-xs btn-' . $options->string('style') . '"' . $confirmation . '>' . $options->string('text') . '</a>';
        }
    }

    public function process($request, $response)
    {
        // @todo input data as input
        $this->request = $request;
        $this->response = $response;
        if (!$request->isXmlHttpRequest() && $request->get('csv') === null && $request->get('json') === null) {
            return false;
        }
        if ($request->get('csv') === null) {
            $this->setStart($request->get('start') ?: 0);
            $this->setLength($request->get('length') ?: 10);
        } else {
            $this->setStart(0);
            $this->setLength(10000);
        }
        $search = $request->get('search');
        $this->setSearch(isset($search['value']) ? $search['value'] : null);
        $this->setInputColumns($request->get('columns') ?: []);
        $orders = $request->get('order');
        if (is_array($orders)) {
            foreach ($orders as $order) {
                if (isset($order['column']) && isset($order['dir'])) {
                    $this->addOrder($order['column'], $order['dir']);
                }
            }
        }
        $this->processSource();

        if ($request->get('csv') === null) {
            return $this->sendJson($request);
        } else {
            return $this->sendCsv();
        }
    }

    protected function sendJson($request)
    {
        $data = $this->getData();
        $result = [];
        $columns = array_values($this->getColumns());
        foreach ($data as $r => $row) {
            $indexedRow = [];
            foreach ($columns as $c => $column) {
                $indexedRow[$column->getName()] = $row[$c];
            }
            foreach ($this->getRowFormatters() as $rowFormmater) {
                $format = $rowFormmater($indexedRow, 'html');
                if ($format['class']) {
                    $result[$r]['DT_RowClass'] = $format['class'];
                }
            }
            foreach ($columns as $c => $column) {
                $result[$r][$column->getKey()] = $column->format($row[$c], $indexedRow, 'html');
            }
        }
        $this->response->json([
            'draw' => (int) $request->get('draw'),
            'recordsTotal' => $this->getRecordsTotal(),
            'recordsFiltered' => $this->getRecordsFiltered(),
            'data' => $result,
            'meta' => $this->getMeta(),
        ]);
        return true;
    }

    protected function sendCsv()
    {
        $this->response->callback(function () {
            $data = $this->getData();

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
        });
        return true;
    }

    public function getId()
    {
        if (!$this->id) {
            $hash = $this->getIdHash();
            foreach ($this->getColumns() as $column) {
                $hash[] = $column->getName();
            }
            $this->id = 'datatable-' . md5(implode(':', $hash));
        }
        return $this->id;
    }

    public function getIdHash(): array
    {
        return [];
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    public function getColumn($name)
    {
        foreach ($this->columns as $column) {
            if ($column->getName() == $name) {
                return $column;
            }
        }
        return null;
    }

    public function getColumnIndex($name)
    {
        foreach ($this->columns as $i => $column) {
            if ($column->getName() == $name) {
                return $i;
            }
        }
        return null;
    }

    protected function spliceColumn($column, $offset = null)
    {
        if ($offset === null) {
            $this->columns[] = $column;
        } else {
            array_splice($this->columns, $offset, 0, [$column]);
        }
        return $column;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    public function getRecordsTotal()
    {
        return $this->recordsTotal;
    }

    public function setRecordsTotal($recordsTotal)
    {
        $this->recordsTotal = $recordsTotal;
        return $this;
    }

    public function getRecordsFiltered()
    {
        return $this->recordsFiltered;
    }

    public function setRecordsFiltered($recordsFiltered)
    {
        $this->recordsFiltered = $recordsFiltered;
        return $this;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function setStart($start)
    {
        $this->start = $start;
        return $this;
    }

    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getSearch()
    {
        return $this->search;
    }

    public function setSearch($search)
    {
        $this->search = $search;
        return $this;
    }

    public function getInputColumns()
    {
        return $this->inputColumns;
    }

    public function setInputColumns($inputColumns)
    {
        $this->inputColumns = $inputColumns;
        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    public function addOrder($column, $direction)
    {
        $this->order[$column] = $direction;
        return $this;
    }

    public function getDefaultOrder()
    {
        return $this->defaultOrder;
    }

    public function setDefaultOrder(array $defaultOrder)
    {
        $defaultOrder = array_map(function ($defaultOrder) {
            if (is_string($defaultOrder[0])) {
                return [
                    $this->getColumnIndex($defaultOrder[0]),
                    $defaultOrder[1],
                ];
            }
            return $defaultOrder;
        }, $defaultOrder);
        $this->defaultOrder = $defaultOrder;
        return $this;
    }

    public function getSaveState()
    {
        return $this->saveState;
    }

    public function setSaveState($saveState)
    {
        $this->saveState = $saveState;
        return $this;
    }

    public function getTableButtons()
    {
        return $this->tableButtons;
    }

    public function setTableButtons($tableButtons)
    {
        $this->tableButtons = $tableButtons;
        return $this;
    }

    public function addTableButton(array $options)
    {
        $this->tableButtons[] = array_merge([
            'name' => null,
            'type' => null,
            'text' => null,
            'href' => null,
            'class' => null,
            'confirm' => null,
        ], $options);
    }

    public function getJsInstance()
    {
        return "RhinoDataTables['{$this->getId()}']";
    }

    public function getRowFormatters()
    {
        return $this->rowFormatters;
    }

    public function setRowFormatters(array $rowFormatters)
    {
        $this->rowFormatters = $rowFormatters;
        return $this;
    }

    public function addRowFormatter(callable $rowFormatter)
    {
        $this->rowFormatters[] = $rowFormatter;
        return $this;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function setMeta(array $meta)
    {
        $this->meta = $meta;
        return $this;
    }

    public function setMetaValue(string $key, $value)
    {
        $this->meta[$key] = $value;
        return $this;
    }
}
