<?php

use Rhino\DataTable\ArrayDataTable;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/includes/autoload.php';

$dataSet = require_once __DIR__ . '/includes/data-set.php';

$dataTable = new ArrayDataTable($dataSet);
$dataTable->addColumn('id')->setIndex(0);
$dataTable->addColumn('first_name')->setIndex(1);
$dataTable->addColumn('last_name')->setIndex(2);
$dataTable->addColumn('email')->setIndex(3);
$dataTable->addColumn('gender')->setIndex(4);
$dataTable->addColumn('ip_address')->setIndex(5);

$dataTable->addRowFormatter(function ($row) {
    return [
        'class' => !$row['ip_address'] ? 'bg-danger' : null,
    ];
});

$request = Request::createFromGlobals();

if ($dataTable->process($request)) {
    return $dataTable->sendResponse();
}

require_once __DIR__ . '/includes/header.php';
echo $dataTable->render();
require_once __DIR__ . '/includes/footer.php';
