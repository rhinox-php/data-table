<?php

namespace Rhino\DataTable;

use Rhino\DataTable\Preset\Preset;

class Column
{
    protected DataTable $dataTable;
    protected string $name;
    protected $header = null;
    protected string $className = '';
    // protected $preset;
    protected array $formatters = [];
    protected bool $exportable = true;
    protected bool $visible = true;
    protected bool $searchable = true;
    protected bool $sortable = true;
    protected $defaultColumnFilter = null;
    protected $filterEnabled = true;
    protected $filterSelect = [];
    protected $filterDateRange = [];
    protected $footer;

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
        return $this->header !== null ? $this->header : $this->humanise($this->getName());
    }

    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    public function getFormatters(): array
    {
        return $this->formatters;
    }

    public function addFormatter(callable $formatter): self
    {
        $this->formatters[] = $formatter;
        return $this;
    }

    public function format($value, $row, $type)
    {
        // $preset = $this->getPreset();
        // switch ($preset['preset'] ?? null) {
        //     case 'human':
        //         $data = explode(',', $value);
        //         array_walk($data, function (&$value) {
        //             $value = $this->humanise($value);
        //         });
        //         $data = array_filter($data);
        //         $value = htmlspecialchars(implode(', ', $data));
        //         break;

        //     case 'group':
        //         $data = explode(',', $value);
        //         $data = array_filter($data);
        //         $sliced = false;
        //         if ($preset['options']) {
        //             if (count($data) > $preset['options']) {
        //                 $data = array_slice($data, 0, $preset['options']);
        //                 $sliced = true;
        //             }
        //         }
        //         $value = htmlspecialchars(implode(', ', $data));
        //         if ($sliced) {
        //             $value .= '...';
        //         }
        //         break;

        //     case 'link':
        //         if ($type == 'html' && $value) {
        //             $url = preg_replace_callback('/{(.*?)}/', function ($matches) use ($row) {
        //                 if (isset($row[$matches[1]])) {
        //                     return $row[$matches[1]];
        //                 }
        //                 return $matches[1];
        //             }, $preset['options']);
        //             $value = '<a href="' . $url . '">' . htmlspecialchars($value) . '</a>';
        //         }
        //         break;

        //     case 'groupLink':
        //         $data = explode(',', $value);
        //         if ($type == 'html' && $value) {
        //             array_walk($data, function (&$value) use ($preset) {
        //                 $url = str_replace('{value}', $value, $preset['options']);
        //                 $value = '<a href="' . $url . '">' . htmlspecialchars($value) . '</a>';
        //             });
        //         }
        //         $data = array_filter($data);
        //         $value = implode(', ', $data);
        //         break;

        //     case 'prefix':
        //         if ($value !== null && $preset['options']['prefix'] !== null) {
        //             $value = $preset['options']['prefix'] . $value;
        //         }
        //         break;

        //     case 'suffix':
        //         if ($value !== null && $preset['options']['suffix'] !== null) {
        //             $value = $value . $preset['options']['suffix'];
        //         }
        //         break;

        //     case 'length':
        //         if ($value !== null && $preset['options']['unit'] !== null) {
        //             $unit = new \PhpUnitsOfMeasure\PhysicalQuantity\Length($value, 'mm');
        //             $value = $unit->toUnit($preset['options']['unit']);
        //             if ($preset['options']['round'] !== null) {
        //                 $value = number_format($value, 1);
        //             }
        //             if ($preset['options']['suffix'] !== null) {
        //                 $value .= $preset['options']['suffix'];
        //             }
        //         }
        //         break;

        //     case 'money':
        //         if ($value !== null) {
        //             $value = '$ ' . number_format($value ?: 0, 2);
        //         }
        //         break;

        //     case 'percent':
        //         if ($value !== null) {
        //             $value = round($value) . ' %';
        //         }
        //         break;

        //     case 'trim':
        //     case 'trimHtml':
        //         // @todo show more link
        //         if (strlen($value) > 100) {
        //             $value = htmlspecialchars(substr($value, 0, 100)) . '...';
        //         } else {
        //             $value = htmlspecialchars($value);
        //         }
        //         break;

        //     case 'bytes':
        //         if ($value !== null) {
        //             // @todo fix column filtering
        //             $precision = 2;
        //             $units = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
        //             $pow = floor(($value ? log($value) : 0) / log(1024));
        //             $pow = min($pow, count($units) - 1);
        //             $value /= pow(1024, $pow);
        //             $value = round($value, $precision) . ' ' . $units[$pow];
        //         }
        //         break;

        //     case 'enum':
        //         if ($value !== null) {
        //             // @todo fix column filtering
        //             $value = $preset['options'][$value] ?? $value;
        //         }
        //         break;

        //     case 'date':
        //         if ($value) {
        //             try {
        //                 $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        //                 $value = $date->format($preset['options']['format'] ?? 'Y-m-d');
        //             } catch (\Exception $exception) {
        //             }
        //         }
        //         break;

        //     case 'dateTime':
        //         if ($value) {
        //             try {
        //                 $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        //                 if (isset($preset['options']['timeZone'])) {
        //                     $date->setTimezone($preset['options']['timeZone']);
        //                 }
        //                 $value = $date->format($preset['options']['format'] ?? 'Y-m-d H:i:s');
        //             } catch (\Exception $exception) {
        //             }
        //         }
        //         break;

        //     case 'html':
        //         break;

        //     default:
        //         $preset = null;
        //         break;
        // }
        $formatters = $this->getFormatters();
        if (empty($formatters)) {
            // Fallback to HTML encoded formatter
            if ($type === 'html') {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
            }
        }
        foreach ($formatters as $formatter) {
            $value = $formatter($value, $row, $type);
            if ($value instanceof \Generator || $value instanceof \Iterator) {
                $value = iterator_to_array($value);
            }
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $value = (string) $value;
        }
        return $value;
    }

    // public function getPreset()
    // {
    //     return $this->preset;
    // }

    public function setPreset(Preset $preset): Column
    {
        $preset->configure($this);
        return $this;

        // switch ($preset) {
        //     case 'number':
        //     case 'percent':
        //     case 'money':
        //         $this->addClass('rhinox-data-table-align-right rhinox-data-table-number');
        //         break;

        //     case 'bool':
        //         $this->setFormat(function ($value, $row, $type) {
        //             return $value ? 'Yes' : '-';
        //         });
        //         break;

        //     case 'created':
        //         $this->setHeader('Created');
        //         $this->addClass('rhinox-data-table-number');
        //         break;

        //     case 'updated':
        //         $this->setHeader('Updated');
        //         $this->addClass('rhinox-data-table-number');
        //         break;

        //     case 'date':
        //         $this->addClass('rhinox-data-table-number');
        //         $this->setFilterDateRange([
        //             'timeZone' => $options,
        //         ]);
        //         break;

        //     case 'dateTime':
        //         $this->addClass('rhinox-data-table-number');
        //         $this->setFilterDateRange($options);
        //         break;

        //     case 'jsonArray':
        //         $this->setFormat(function ($value, $row, $type) {
        //             if ($type == 'html') {
        //                 if ($value) {
        //                     $result = [];
        //                     foreach (json_decode($value) as $value) {
        //                         $result[] = '<li>' . $value . '</li>';
        //                     }
        //                     return '<ul class="rhinox-data-table-json-array">' . implode('', $result) . '</ul>';
        //                 }
        //             }
        //         });
        //         break;

        //     case 'jsonArrayKey':
        //         $this->setFormat(function ($value, $row, $type) use ($options) {
        //             if ($type == 'html') {
        //                 if ($value) {
        //                     $result = [];
        //                     foreach (json_decode($value) as $key => $checked) {
        //                         if ($checked) {
        //                             if ($options) {
        //                                 $key = $options($key);
        //                             }
        //                             $result[] = '<li>' . $key . '</li>';
        //                         }
        //                     }
        //                     natcasesort($result);
        //                     return '<ul class="rhinox-data-table-json-array">' . implode('', $result) . '</ul>';
        //                 }
        //             }
        //         });
        //         break;

        //     case 'jsonObject':
        //         $this->setFormat(function ($value, $row, $type) {
        //             if ($type == 'html') {
        //                 if ($value) {
        //                     $result = [];
        //                     foreach (json_decode($value) as $key => $value) {
        //                         $result[] = '<li><b>' . $key . ':</b> ' . $value . '</li>';
        //                     }
        //                     return '<ul class="rhinox-data-table-json-object">' . implode('', $result) . '</ul>';
        //                 }
        //             }
        //         });
        //         break;

        //     case 'jsonString':
        //         $this->setFormat(function ($value, $row, $type) {
        //             return json_decode($value);
        //         });
        //         break;

        //     case 'array':
        //         $this->setFormat(function ($value, $row, $type) {
        //             if ($value && is_array($value)) {
        //                 $result = [];
        //                 foreach ($value as $item) {
        //                     $result[] = '<li>' . $item . '</li>';
        //                 }
        //                 natcasesort($result);
        //                 return '<ul class="rhinox-data-table-json-array">' . implode('', $result) . '</ul>';
        //             }
        //             return $value;
        //         });
        //         break;
        // }
        // $this->preset = [
        //     'preset' => $preset,
        //     'options' => $options,
        // ];
        // return $this;
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

    public function getFilterDateRange()
    {
        return $this->filterDateRange;
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

    public function getClassName()
    {
        return $this->className;
    }

    public function addClass($class)
    {
        $this->className .= ' ' . $class;
        return $this;
    }

    public function humanise($value)
    {
        if ($value == 'id') {
            return 'ID';
        }
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        $value = preg_replace('/\bid\b/i', 'ID', $value);
        $value = ucwords($value);
        return $value;
    }

    public function isExportable()
    {
        return true;
    }

    public function getFooter()
    {
        return $this->footer;
    }

    public function setFooter($footerType, $footerOptions = [])
    {
        $this->footer = [$footerType, $footerOptions];
        return $this;
    }

    public function getDataTable(): DataTable
    {
        return $this->dataTable;
    }
}
