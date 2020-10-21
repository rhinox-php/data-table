<?php

namespace Rhino\DataTable;

use Rhino\DataTable\Preset\Human;
use Rhino\DataTable\Preset\Preset;

class Column
{
    private DataTable $dataTable;
    private string $name;
    private $header = null;
    private string $className = '';
    private array $presets = [];
    private array $formatters = [];
    private bool $exportable = true;
    private bool $visible = true;
    private bool $searchable = true;
    private bool $sortable = true;
    private $defaultColumnFilter = null;
    private $filterEnabled = true;
    private $filterSelect = [];
    private $filterDateRange = [];
    private $filterNumeric = [];
    private ?Footer $footer = null;

    public function __construct(DataTable $dataTable, $name)
    {
        $this->dataTable = $dataTable;
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getKey()
    {
        return str_replace('_', '', lcfirst(ucwords($this->getName(), '_')));
    }

    public function getHeader()
    {
        return $this->header !== null ? $this->header : Human::humanise($this->getName());
    }

    public function setHeader(string $header)
    {
        $this->header = $header;
        return $this;
    }

    public function getFormatters(): array
    {
        return $this->formatters;
    }

    public function addFormatter(callable $formatter)
    {
        $this->formatters[] = $formatter;
        return $this;
    }

    public function format($value, array $row, string $type)
    {
        $formatters = $this->getFormatters();
        if (empty($formatters)) {
            // Fallback to HTML encoded formatter
            if ($type === 'html') {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
            }
        } else {
            foreach ($formatters as $formatter) {
                $value = $formatter($value, $row, $type);
                if ($value instanceof \Generator || $value instanceof \Iterator) {
                    $value = iterator_to_array($value);
                }
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $value = (string) $value;
            }
        }
        return $value;
    }

    public function addPreset(Preset $preset)
    {
        $this->presets[] = $preset;
        $preset->configure($this);
        return $this;
    }

    public function isExportable(): bool
    {
        return $this->exportable;
    }

    public function setExportable(bool $exportable)
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

    public function getFilterSelect(?string $label = null)
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

    public function getFilterDateRange()
    {
        return $this->filterDateRange;
    }

    public function setFilterDateRange($filterDateRange)
    {
        $this->filterDateRange = $filterDateRange;
        return $this;
    }

    public function getFilterNumeric()
    {
        return $this->filterNumeric;
    }

    public function setFilterNumeric($filterNumeric)
    {
        $this->filterNumeric = $filterNumeric;
        return $this;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function addClass(string $class)
    {
        $this->className .= ' ' . $class;
        return $this;
    }

    // public function humanise($value)
    // {
    //     if ($value == 'id') {
    //         return 'ID';
    //     }
    //     $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
    //     $value = preg_replace('/\bid\b/i', 'ID', $value);
    //     $value = ucwords($value);
    //     return $value;
    // }

    public function getFooter(): ?Footer
    {
        return $this->footer;
    }

    public function setFooter(Footer $footer)
    {
        $this->footer = $footer;
        return $this;
    }

    public function getDataTable(): DataTable
    {
        return $this->dataTable;
    }
}
