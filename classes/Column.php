<?php
namespace Rhino\DataTable;

class Column
{
    protected $name;
    protected $label;
    protected $format;
    protected $position;

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

    public function getLabel()
    {
        return $this->label ?: $this->name;
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

    public function format($column, $row)
    {
        $formatter = $this->getFormat();
        if ($formatter) {
            $result = $formatter($column, $row);
            if (is_array($result)) {
                $result = implode('', $result);
            } else {
                $result = (string) $result;
            }
            return $result;
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
}
