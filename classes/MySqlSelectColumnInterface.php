<?php

namespace Rhino\DataTable;

interface MySqlSelectColumnInterface
{
    public function getFilterQuery(): string;
}
