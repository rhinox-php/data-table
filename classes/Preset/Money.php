<?php

namespace Rhino\DataTable\Preset;

class Money extends Number
{
    public function __construct()
    {
        $this->setDecimalPlaces(2);
        $this->setNullValue('-');
        $this->setPrefix('$ ');
    }
}
