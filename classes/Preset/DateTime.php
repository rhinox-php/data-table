<?php

namespace Rhino\DataTable\Preset;

class DateTime extends Date
{
    public function __construct()
    {
        $this->setFormat('Y-m-d H:i:s');
    }
}
