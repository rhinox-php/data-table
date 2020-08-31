<?php

namespace Rhino\DataTable;

class Dropdown
{
    private $buttons;

    public function __construct(array $buttons)
    {
        $this->buttons = $buttons;
    }

    public function render()
    {
        $buttons = '';
        foreach ($this->buttons as $button) {
            $buttons .= $button->addClass('dropdown-item')->render();
        }
        $id = uniqid('uid-');
        $result = <<<HTML
            <div class="dropdown">
                <button class="btn btn-primary btn-sm j-btn-data-table dropdown-toggle" type="button" id="$id" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                <div class="dropdown-menu" aria-labelledby="$id">
                    $buttons
                </div>
            </div>
        HTML;
        return $result;
    }
}
