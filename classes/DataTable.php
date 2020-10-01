<?php

namespace Rhino\DataTable;

use Rhino\InputData\InputData;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

// @todo implement multiple column sorting
// @todo Check for consistency for works like sort/order filter/search
abstract class DataTable
{
    private Request $request;
    private Response $response;
    private ?string $id = null;
    private array $columns = [];
    private array $data;
    private array $footerRows = [];
    private int $recordsTotal;
    private int $recordsFiltered;
    private int $start;
    private int $length;
    private ?string $search;
    private InputData $inputColumns;
    private ?array $order = null;
    private ?array $defaultOrder = null;
    private array $tableButtons = [];
    private array $rowFormatters = [];
    private array $meta = [];
    private string $url = '';
    private bool $saveState = true;
    // @todo why both saveState and rememberSettingsEnabled
    private bool $rememberSettingsEnabled = true;
    private string $exportFileName = 'export';
    private bool $hasAction = false;
    private bool $hasSelect = false;
    private bool $debug = false;

    /**
     * Draw counter. This is used by DataTables to ensure that the Ajax returns from server-side processing requests are drawn in sequence by DataTables (Ajax requests are asynchronous and thus can return out of sequence). This is used as part of the draw return parameter.
     *
     * @see https://datatables.net/manual/server-side
     */
    private ?int $drawCounter = null;

    public function __construct()
    {
    }

    abstract protected function processSource(InputData $input);

    // abstract protected function iterateRows(InputData $input, string $outputType): \Generator;

    public function sendResponse()
    {
        $response = $this->getResponse();
        $response->send();
        // if ($response instanceof \Closure) {
        //     $response();
        //     return;
        // }
        // echo json_encode($response);
    }

    public function render()
    {
        ob_start();
        $dataTable = $this;
        require \Rhino\DataTable\ROOT . '/views/bootstrap.php';
        return ob_get_clean();
    }

    // public function createButton(array $options)
    // {
    //     $options = new \Rhino\Core\InputData($options);
    //     $confirmation = '';
    //     if ($options->string('confirm')) {
    //         $confirmation = ' onclick="if (!confirm(\'' . htmlspecialchars($options->string('confirm'), ENT_QUOTES) . '\')) { event.stopImmediatePropagation(); event.preventDefault(); }"';
    //     }
    //     if ($options->bool('action')) {
    //         return '
    //             <form action="' . $options->string('action') . '" method="post">
    //                 <button class="btn btn-xs btn-' . $options->string('style') . '"' . $confirmation . '>' . $options->string('text') . '</button>
    //             </form>
    //         ';
    //     } else {
    //         return '<a href="' . $options->string('href') . '" class="btn btn-xs btn-' . $options->string('style') . '"' . $confirmation . '>' . $options->string('text') . '</a>';
    //     }
    // }

    public static function createButton()
    {
        return new Button();
    }

    public static function createDropdown(array $buttons)
    {
        return new Dropdown($buttons);
    }

    public function process(Request $request)
    {
        // @todo input data as input
        $input = new InputData(array_merge($request->query->all(), $request->request->all()));
        $this->setDrawCounter($input->int('draw'));

        // Apply URL filters
        foreach ($input->arr('filter') as $columnName => $value) {
            foreach ($this->getColumns() as $column) {
                if ($column->getName() == $columnName->string()) {
                    $column->setDefaultColumnFilter($value->string());
                }
            }
        }

        if (!$request->isXmlHttpRequest() && !$input->bool('csv') && !$input->bool('json')) {
            return false;
        }
        if ($request->get('csv') === null) {
            $this->setStart($input->int('start') ?: 0);
            $this->setLength($input->int('length') ?: 10);
        } else {
            $this->setStart(0);
            // @todo allow setting custom default limit
            $this->setLength(10000);
        }
        $this->setSearch($input->string('search.value', null));
        $this->setInputColumns($input->arr('columns'));
        foreach ($input->arr('order') as $order) {
            if ($order->string('column') !== '' && $order->string('dir')) {
                $this->addOrder($order->string('column'), $order->string('dir'));
            }
        }
        $this->processSource($input);

        if ($request->get('csv') === null) {
            return $this->sendJson();
        } else {
            return $this->sendCsv();
        }
    }

    public function getJsonResponseData(): array
    {
        $data = $this->getData();
        $columns = $this->getColumns();
        foreach ($data as $rowIndex => $row) {
            // Get row data indexed by column key
            $indexedRow = [];
            foreach ($columns as $columnIndex => $column) {
                $indexedRow[$column->getKey()] = $row[$columnIndex];
            }

            // Run column formatters
            foreach ($columns as $columnIndex => $column) {
                $indexedRow[$column->getKey()] = $column->format($indexedRow[$column->getKey()], $indexedRow, 'html');
            }

            // Run row formatters
            foreach ($this->getRowFormatters() as $rowFormatter) {
                $format = $rowFormatter($indexedRow, 'html');
                if ($format['class']) {
                    $indexedRow['DT_RowClass'] = $format['class'];
                }
            }
            $data[$rowIndex] = $indexedRow;
        }

        // Format footers
        $footerRows = $this->getFooterRows();
        foreach ($footerRows as $footerRowIndex => $footerRow) {
            if (!$footerRow) {
                continue;
            }
            $indexedRow = [];
            foreach ($columns as $columnIndex => $column) {
                $indexedRow[$column->getKey()] = $footerRow[$columnIndex];
            }
            foreach ($columns as $columnIndex => $column) {
                $footerRows[$footerRowIndex][$columnIndex] = $column->format($indexedRow[$column->getKey()], $indexedRow, 'html');
            }
        }

        return [
            'draw' => $this->getDrawCounter(),
            'recordsTotal' => $this->getRecordsTotal(),
            'recordsFiltered' => $this->getRecordsFiltered(),
            'data' => $data,
            'footerRows' => $footerRows,
            'meta' => $this->getMeta(),
        ];
    }

    public function getResponse()
    {
        return $this->response;
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

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->getName() == $name) {
                return $column;
            }
        }
        return null;
    }

    public function getColumnIndex($name): ?int
    {
        foreach ($this->columns as $i => $column) {
            if ($column->getName() == $name) {
                return $i;
            }
        }
        return null;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function getFooterRows()
    {
        return $this->footerRows;
    }

    public function setFooterRows(array $footerRows)
    {
        $this->footerRows = $footerRows;
        return $this;
    }

    public function getRecordsTotal()
    {
        return $this->recordsTotal;
    }

    public function setRecordsTotal(int $recordsTotal)
    {
        $this->recordsTotal = $recordsTotal;
        return $this;
    }

    public function getRecordsFiltered()
    {
        return $this->recordsFiltered;
    }

    public function setRecordsFiltered(int $recordsFiltered)
    {
        $this->recordsFiltered = $recordsFiltered;
        return $this;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function setStart(int $start)
    {
        $this->start = $start;
        return $this;
    }

    public function setLength(int $length)
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

    public function setSearch(?string $search)
    {
        $this->search = $search;
        return $this;
    }

    public function getInputColumns(): InputData
    {
        return $this->inputColumns;
    }

    public function setInputColumns(InputData $inputColumns)
    {
        $this->inputColumns = $inputColumns;
        return $this;
    }

    public function getOrder(): ?array
    {
        return $this->order;
    }

    public function addOrder(string $column, string $direction)
    {
        $this->order[$column] = $direction;
        return $this;
    }

    public function getDefaultOrder(): ?array
    {
        return $this->defaultOrder;
    }

    public function setDefaultOrder(?string $column, string $direction = 'asc')
    {
        if ($column === null) {
            $this->defaultOrder = null;
            return $this;
        }
        if ($direction !== 'asc' && $direction !== 'desc') {
            throw new Exception\ConfigException('Invalid default order direction, must be "asc" or "desc", got: ' . $direction);
        }
        $this->defaultOrder = [[
            $this->getColumnIndex($column),
            $direction,
        ]];
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
        return $this;
    }

    public function getExportFileName(): string
    {
        return $this->exportFileName;
    }

    public function setExportFileName(string $exportFileName)
    {
        $this->exportFileName = $exportFileName;
        return $this;
    }

    public function addSelect(string $checkboxName = 'row')
    {
        return $this->columns[] = new Select($this, 'select' . count($this->columns), $checkboxName);
    }

    public function hasSelect(): bool
    {
        foreach ($this->getColumns() as $column) {
            if ($column instanceof Select) {
                return true;
            }
        }
        return false;
    }

    public function addAction($callback): Action
    {
        $action = new Action($this, $callback, 'action' . count($this->columns));
        if (!$this->hasAction) {
            $this->hasAction = true;
        }
        $action->setHeader('');
        $action->addClass('rhinox-data-table-nowrap');
        return $this->columns[] = $action;
    }

    public function getDrawCounter(): ?int
    {
        return $this->drawCounter;
    }

    public function setDrawCounter(int $drawCounter)
    {
        $this->drawCounter = $drawCounter;
        return $this;
    }

    protected function sendJson(): bool
    {
        $this->response = new JsonResponse($this->getJsonResponseData());
        return true;
    }

    protected function sendCsv()
    {
        $this->response = new StreamedResponse(null, 200, [
            'Content-Type' => 'application/csv',
            'Content-Disposition' => 'attachment; filename=' . $this->getExportFileName() . '.csv',
        ]);
        $this->response->setCache([
            'no_cache' => true,
        ]);
        $this->response->setCallback(function () {
            $data = $this->getData();

            $columns = $this->getColumns();

            $outputStream = fopen('php://output', 'w');
            $outputRow = [];
            foreach ($columns as $column) {
                if ($column->isExportable()) {
                    $outputRow[] = $column->getHeader();
                }
            }
            fputcsv($outputStream, $outputRow);

            foreach ($data as $row) {
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
                fputcsv($outputStream, $outputRow);
            }
            // @todo footers
            fclose($outputStream);
        });
        return true;
    }

    protected function getIdHash(): array
    {
        return [];
    }

    protected function spliceColumn($column, ?int $index = null)
    {
        if ($index === null) {
            $this->columns[] = $column;
        } else {
            array_splice($this->columns, $index, 0, [$column]);
        }
        return $column;
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
        return $this;
    }
}
