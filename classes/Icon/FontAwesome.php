<?php

namespace Rhino\DataTable\Icon;

class FontAwesome
{
    public static function getMarkup(string $iconName): string
    {
        return '<i class="fa fa-' . $iconName . '"></i>';
    }
}
