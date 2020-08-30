<?php

use Rhino\DataTable\MySqlDataTable;
use Rhino\DataTable\Preset;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/includes/autoload.php';

$pdo = require_once __DIR__ . '/includes/pdo.php';

$dataTable = new MySqlDataTable($pdo, 'line_items');
$dataTable->addJoin('LEFT JOIN products ON products.id = line_items.product_id');
$dataTable->addGroupBy('line_items.id');

$dataTable->addColumn('id');
$dataTable->addColumn('quantity');
$dataTable->addColumn('order_id');
$dataTable->addColumn('product_id');
$dataTable->addColumn('product_name')->setQuery('products.name');
$dataTable->addColumn('product_code')->setQuery('products.code');
$dataTable->setDefaultOrder('id', 'asc');

$request = Request::createFromGlobals();

if ($dataTable->process($request)) {
    return $dataTable->sendResponse();
}

require_once __DIR__ . '/includes/header.php';
echo $dataTable->render();
require_once __DIR__ . '/includes/footer.php';
