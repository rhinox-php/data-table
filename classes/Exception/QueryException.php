<?php

namespace Rhino\DataTable\Exception;

class QueryException extends DataTableException
{
    public function __construct($message, array $errorInfo, string $sql)
    {
        parent::__construct($message . ' ' . $errorInfo[0] . ' ' . $errorInfo[1] . ' ' . $errorInfo[2] . ' SQL: ' . $sql);
    }
}
