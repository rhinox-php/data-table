<?php

namespace Rhino\DataTable;

class Select extends Column
{
    protected bool $searchable = false;
    protected bool $sortable = false;
    protected string $checkboxName;

    public function __construct(DataTable $dataTable, string $name, string $checkboxName)
    {
        parent::__construct($dataTable, $name);
        $this->checkboxName = $checkboxName;

        // Default header is blank
        $this->setHeader('');
    }

    public function format($value, $row, $type)
    {
        // @todo escape
        // @todo support other ui libraries apart from bootstrap
        // @todo label
        $id = uniqid('checkbox-');
        return '
            <div class="custom-control custom-checkbox">
                <input type="hidden" name="datatable[' . $row[$this->name] . ']" value="0" />
                <input type="checkbox" class="custom-control-input" id="' . $id . '" name="datatable[' . $row[$this->name] . ']" value="1" data-row="' . json_encode($row) . '" />
                <label class="custom-control-label" for="' . $id . '"></label>
            </div>
        ';
        // return (new TagCheckbox(new InputData(['name' => 'datatable[' . $row[$this->checkboxName] . ']', 'attributes' => ['data-row' => json_encode($row)],])))->render();
    }

    public function getClassName()
    {
        return 'rhinox-data-table-select';
    }

    public function isExportable()
    {
        return false;
    }
}
