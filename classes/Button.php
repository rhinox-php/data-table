<?php

namespace Rhino\DataTable;

class Button
{
    protected ?string $url = null;
    protected ?string $text = null;
    protected array $classes = [];
    protected array $attributes = [];
    protected ?string $confirmation = null;
    protected array $data = [];
    protected ?string $icon = null;
    protected ?string $target = null;
    protected ?string $csrfToken = null;
    protected bool $visible = true;

    public function render(): ?string
    {
        if (!$this->getVisible()) {
            return null;
        }
        $confirmation = '';
        if ($this->getConfirmation()) {
            $confirmation = ' onclick="if (!confirm(\'' . $this->escapeHtml($this->getConfirmation()) . '\')) { event.stopImmediatePropagation(); event.preventDefault(); }"';
        }
        switch ($this->getIcon()) {
            case 'edit':
                $icon = '<i class="glyphicon glyphicon-pencil"></i>';
                break;

            case 'link':
                $icon = '<i class="glyphicon glyphicon-link"></i>';
                break;

            default:
                $icon = '';
                break;
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
            foreach ($this->getData() as $key => $value) {
                $inputs[] = '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
            }
            // @todo move csrf token to laravel specific data table
            return '
                <form action="' . $this->getUrl() . '" method="post" ' . $target . ' class="rhinox-data-table-button-form">
                    <input type="hidden" name="_token" value="' . $this->getCsrfToken() . '" />
                    ' . implode(PHP_EOL, $inputs) . '
                    <button class="btn ' . $classes . ' rhinox-data-table-button"' . $confirmation . ' ' . $attributes . '>' . $icon . $this->getText() . '</button>
                </form>
            ';
        }
        return '<a href="' . $this->getUrl() . '" ' . $confirmation . ' class="' . $classes . '" ' . $attributes . ' ' . $target . '>' . $icon . $this->getText() . '</a>';
    }

    protected function escapeHtml(string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
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

    public function setUrl($url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setText($text): self
    {
        $this->text = $text;
        return $this;
    }

    public function setConfirmation($confirmation): self
    {
        $this->confirmation = $confirmation;
        return $this;
    }

    public function setData($data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setIcon($icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setClasses($classes): self
    {
        $this->classes = $classes;
        return $this;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function addClass($class): self
    {
        $this->classes[] = $class;
        return $this;
    }

    public function setAttributes($attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function addAttribute($name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget($target): self
    {
        $this->target = $target;
        return $this;
    }

    public function getCsrfToken(): ?string
    {
        return $this->csrfToken;
    }

    public function setCsrfToken($csrfToken): self
    {
        $this->csrfToken = $csrfToken;
        return $this;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;
        return $this;
    }
}
