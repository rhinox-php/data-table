<?php
namespace Rhino\DataTable;

class Column
{
    protected $name;
    protected $label = null;
    protected $format;
    protected $position;
    protected $exportable = true;
    protected $visible = true;
    protected $defaultColumnFilter = null;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getKey() {
        return str_replace('_', '', lcfirst(ucwords($this->getName(), '_')));
    }

    public function getLabel()
    {
        return $this->label !== null ? $this->label : $this->name;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    public function format($column, $row, $type)
    {
        $formatter = $this->getFormat();
        if ($formatter) {
            $result = $formatter($column, $row, $type);
            if (is_array($result)) {
                $result = implode('', $result);
            } else {
                if ($result instanceof \Generator || $result instanceof \Iterator) {
                    $result = iterator_to_array($result);
                }
                if (is_array($result)) {
                    $result = implode(' ', $result);
                }
                $result = (string) $result;
            }
            return $result;
        } elseif ($type === 'html') {
            return htmlspecialchars($column, ENT_QUOTES, 'UTF-8', false);
        }

        return $column;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    public function isSortable() {
        return true;
    }

    public function isExportable() {
        return $this->exportable;
    }

    public function setExportable($exportable) {
        $this->exportable = $exportable;
        return $this;
    }

    public function isVisible() {
        return $this->visible;
    }

    public function setVisible($visible) {
        $this->visible = $visible;
        return $this;
    }

    public function getDefaultColumnFilter() {
        return $this->defaultColumnFilter;
    }

    public function setDefaultColumnFilter($defaultColumnFilter) {
        $this->defaultColumnFilter = $defaultColumnFilter;
        return $this;
    }
}
