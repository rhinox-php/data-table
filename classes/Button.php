<?php

namespace Rhino\DataTable;

class Button
{
    protected $url;
    protected $text = '';
    protected $color;
    protected $classes = [];
    protected $attributes = [];
    protected $confirmation;
    protected $data = [];
    protected $icon;
    protected $target;
    protected $csrfToken;
    protected $visible = true;

    public function render()
    {
        if (!$this->getVisible()) {
            return;
        }
        $confirmation = '';
        if ($this->getConfirmation()) {
            $confirmation = ' onclick="if (!confirm(\'' . $this->escapeHtml($this->getConfirmation()) . '\')) { event.stopImmediatePropagation(); event.preventDefault(); }"';
        }
        switch ($this->getIcon()) {
            case 'edit': {
                    $icon = '<i class="glyphicon glyphicon-pencil"></i>';
                    break;
                }
            case 'link': {
                    $icon = '<i class="glyphicon glyphicon-link"></i>';
                    break;
                }
            default: {
                    $icon = '';
                    break;
                }
        }
        switch ($this->getColor()) {
            case 'red': {
                    $color = 'red';
                    break;
                }
            default: {
                    $color = 'blue';
                    break;
                }
        }
        $classes = implode(' ', $this->getClasses());
        $attributes = [];
        foreach ($this->getAttributes() as $name => $value) {
            $attributes[] = $name . '="' . $this->escapeHtml($value) . '"';
        }
        $attributes = implode(' ', $attributes);
        $target = '';
        if ($this->target) {
            $target = 'target="' . $this->target . '"';
        }
        if (!empty($this->getData())) {
            $inputs = [];
            foreach ($this->getData() as $key => $value) {
                $inputs[] = '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
            }
            // @todo move csrf token to laravel specific data table
            return '
                <form action="' . $this->getUrl() . '" method="post" ' . $target . ' class="rhinox-data-table-button-form">
                    <input type="hidden" name="_token" value="' . $this->csrfToken . '" />
                    ' . implode(PHP_EOL, $inputs) . '
                    <button class="btn btn-' . $color . ' ' . $classes . ' rhinox-data-table-button"' . $confirmation . ' ' . $attributes . '>' . $icon . $this->getText() . '</button>
                </form>
            ';
        }
        return '<a href="' . $this->getUrl() . '" ' . $confirmation . ' class="' . $classes . '" ' . $attributes . ' ' . $target . '>' . $icon . $this->getText() . '</a>';
    }

    protected function escapeHtml(string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function getConfirmation()
    {
        return $this->confirmation;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getIcon()
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

    public function setColor($color)
    {
        $this->color = $color;
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

    public function getClasses()
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

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function addAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    public function getCsrfToken()
    {
        return $this->csrfToken;
    }

    public function setCsrfToken($csrfToken)
    {
        $this->csrfToken = $csrfToken;
        return $this;
    }

    public function getVisible()
    {
        return $this->visible;
    }

    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
        return $this;
    }
}
