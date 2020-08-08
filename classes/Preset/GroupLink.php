<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class GroupLink extends Preset
{
    private int $limit = 10;
    private string $url;

    public function __construct(string $url)
    {
        $this->setUrl($url);
    }
    public function configure(Column $column): void
    {
        $column->addFormatter([$this, 'format']);
    }

    public function format($value, $row, $type)
    {
        $data = explode(',', $value);
        if ($type == 'html' && $value) {
            // @todo need to preg replace with the row
            array_walk($data, function (&$value) use ($preset) {
                $url = str_replace('{value}', $value, $preset['options']);
                $value = '<a href="' . $url . '">' . htmlspecialchars($value) . '</a>';
            });
        }
        $data = array_filter($data);
        $value = implode(', ', $data);
        return $value;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }
}
