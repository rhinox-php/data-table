<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

class Link extends Preset
{
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
        if ($type == 'html' && $value) {
            $url = preg_replace_callback('/{(.*?)}/', function ($matches) use ($row) {
                if (isset($row[$matches[1]])) {
                    return $row[$matches[1]];
                }
                return $matches[1];
            }, $this->getUrl());
            $value = '<a href="' . $url . '">' . htmlspecialchars($value) . '</a>';
        }
        return $value;
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
