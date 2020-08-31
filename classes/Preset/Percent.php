<?php

namespace Rhino\DataTable\Preset;

class Percent extends Number
{
    public function __construct()
    {
        $this->setDecimalPlaces(0);
        $this->setSuffix(' %');
    }
}
