<?php

namespace Rhino\DataTable\Icon;

class GlyphIcon
{
    public static function getMarkup(string $iconName): string
    {
        return '<i class="glyphicon glyphicon-' . $iconName . '"></i>';
    }
}
