<?php

namespace Rhino\DataTable;

use Rhino\DataTable\Icon\FontAwesome;

class Button
{
    private ?string $url = null;
    private ?string $text = null;
    private array $classes = [];
    private array $attributes = [];
    private ?string $confirmation = null;
    private array $data = [];
    private ?string $icon = null;
    private ?string $target = null;
    private bool $visible = true;

    public function render(): ?string
    {
        if (!$this->getVisible()) {
            return null;
        }
        $confirmation = '';
        if ($this->getConfirmation()) {
            $confirmation = ' onclick="if (!confirm(\'' . $this->escapeHtml($this->getConfirmation()) . '\')) { event.stopImmediatePropagation(); event.preventDefault(); }"';
        }
        $icon = '';
        if ($this->getIcon()) {
            $icon = FontAwesome::getMarkup($this->getIcon()) . ' ';
        }
        $classes = implode(' ', $this->getClasses());
        $attributes = [];
        foreach ($this->getAttributes() as $name => $value) {
            $attributes[] = $name . '="' . $this->escapeHtml($value) . '"';
        }
        $attributes = implode(' ', $attributes);
        $target = '';
        if ($this->getTarget()) {
            $target = 'target="' . $this->getTarget() . '"';
        }
        if (!empty($this->getData())) {
            $inputs = [];
            // @todo allow setting default data from child class for things like csrf tokens
            foreach ($this->getData() as $key => $value) {
                $inputs[] = '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
            }
            return '
                <form action="' . $this->getUrl() . '" method="post" ' . $target . ' class="rhinox-data-table-button-form">
                    ' . implode(PHP_EOL, $inputs) . '
                    <button class="btn ' . $classes . ' rhinox-data-table-button"' . $confirmation . ' ' . $attributes . '>' . $icon . $this->getText() . '</button>
                </form>
            ';
        }
        return '<a href="' . $this->getUrl() . '" ' . $confirmation . ' class="' . $classes . '" ' . $attributes . ' ' . $target . '>' . $icon . $this->getText() . '</a>';
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getConfirmation(): ?string
    {
        return $this->confirmation;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    public function setConfirmation($confirmation)
    {
        $this->confirmation = $confirmation;
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    public function setClasses($classes)
    {
        $this->classes = $classes;
        return $this;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function addClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function addAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
        return $this;
    }

    protected function escapeHtml(string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}
