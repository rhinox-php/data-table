<?php

namespace Rhino\DataTable\Preset;

use Rhino\DataTable\Column;

abstract class Preset
{
    abstract public function configure(Column $column): void;
}
