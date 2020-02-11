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
    protected $searchable = true;
    protected $sortable = true;
    protected $defaultColumnFilter = null;
    protected $filterEnabled = true;
    protected $filterSelect = [];
    protected $filterDateRange = [];

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

    public function getKey()
    {
        return str_replace('_', '', lcfirst(ucwords($this->getName(), '_')));
    }

    public function getLabel()
    {
        return $this->label !== null ? $this->label : $this->humanise($this->getName());
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

    public function setPreset($preset, $options = null): Column
    {
        switch ($preset) {
            case 'number':
            case 'percent':
            case 'money':
                $this->addClass('rx-datatable-align-right rx-datatable-number');
                break;

            case 'bool':
                $this->setFormat(function ($value, $row, $type) {
                    return $value ? 'Yes' : '-';
                });
                break;

            case 'created':
                $this->setLabel('Created');
                $this->addClass('rx-datatable-number');
                break;

            case 'updated':
                $this->setLabel('Updated');
                $this->addClass('rx-datatable-number');
                break;

            case 'dateTime':
                $this->addClass('rx-datatable-number');
                break;

            case 'asset':
                $this->setSearchable(false);
                break;

            case 'jsonArray':
                $this->setFormat(function ($value, $row, $type) {
                    if ($type == 'html') {
                        if ($value) {
                            $result = [];
                            foreach (json_decode($value) as $value) {
                                $result[] = '<li>' . $value . '</li>';
                            }
                            return '<ul class="rx-datatable-json-array">' . implode('', $result) . '</ul>';
                        }
                    }
                });
                break;

            case 'jsonArrayKey':
                $this->setFormat(function ($value, $row, $type) use ($options) {
                    if ($type == 'html') {
                        if ($value) {
                            $result = [];
                            foreach (json_decode($value) as $key => $checked) {
                                if ($checked) {
                                    if ($options) {
                                        $key = $options($key);
                                    }
                                    $result[] = '<li>' . $key . '</li>';
                                }
                            }
                            natcasesort($result);
                            return '<ul class="rx-datatable-json-array">' . implode('', $result) . '</ul>';
                        }
                    }
                });
                break;

            case 'jsonObject':
                $this->setFormat(function ($value, $row, $type) {
                    if ($type == 'html') {
                        if ($value) {
                            $result = [];
                            foreach (json_decode($value) as $key => $value) {
                                $result[] = '<li><b>' . $key . ':</b> ' . $value . '</li>';
                            }
                            return '<ul class="rx-datatable-json-object">' . implode('', $result) . '</ul>';
                        }
                    }
                });
                break;

            case 'jsonString':
                $this->setFormat(function ($value, $row, $type) {
                    return json_decode($value);
                });
                break;
        }
        $this->preset = [
            'preset' => $preset,
            'options' => $options,
        ];
        return $this;
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

    public function isExportable()
    {
        return $this->exportable;
    }

    public function setExportable($exportable)
    {
        $this->exportable = $exportable;
        return $this;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    public function isSearchable()
    {
        return $this->searchable;
    }

    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function isSortable()
    {
        return $this->sortable;
    }

    public function setSortable($sortable)
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function getDefaultColumnFilter()
    {
        return $this->defaultColumnFilter;
    }

    public function setDefaultColumnFilter($defaultColumnFilter)
    {
        $this->defaultColumnFilter = $defaultColumnFilter;
        return $this;
    }

    public function getFilterSelect($label = null)
    {
        if ($label) {
            return isset($this->filterSelect[$label]) ? $this->filterSelect[$label] : null;
        }
        return $this->filterSelect;
    }

    public function hasFilterSelect()
    {
        return !empty($this->filterSelect);
    }

    public function setFilterSelect($filterSelect)
    {
        $this->filterSelect = $filterSelect;
        return $this;
    }

    public function hasFilterDateRange()
    {
        return !empty($this->filterDateRange);
    }

    public function setFilterDateRange($filterDateRange)
    {
        $this->filterDateRange = $filterDateRange;
        return $this;
    }
}
