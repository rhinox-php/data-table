<?php

namespace Rhino\DataTable;

class Footer
{
    private DataTable $dataTable;
    private Column $column;
    private array $text;

    public function __construct(array $text)
    {
        $this->setText($text);
    }

    public function getDataTable(): DataTable
    {
        return $this->dataTable;
    }

    public function setDataTable(DataTable $dataTable)
    {
        $this->dataTable = $dataTable;
        return $this;
    }

    public function getColumn(): Column
    {
        return $this->column;
    }

    public function setColumn(Column $column)
    {
        $this->column = $column;
        return $this;
    }

    public function getText(): array
    {
        return $this->text;
    }

    public function setText(array $text)
    {
        $this->text = $text;
        return $this;
    }
}
