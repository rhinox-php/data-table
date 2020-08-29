<?php

namespace Rhino\DataTable\Icon;

class Material
{
    public static function getMarkup(string $iconName): string
    {
        return '<i class="material-icons">' . $iconName . '</i>';
    }
}
