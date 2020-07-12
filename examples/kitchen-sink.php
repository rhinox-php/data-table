<?php

use Rhino\DataTable\MySqlDataTable;
use Symfony\Component\HttpFoundation\Request;


require_once __DIR__ . '/includes/autoload.php';

$pdo = require_once __DIR__ . '/includes/pdo.php';

$dataTable = new MySqlDataTable($pdo, 'products');
$dataTable->addJoin('LEFT JOIN line_items ON line_items.product_id = products.id');
$dataTable->addWhere('products.created_at > :date', [
    ':date' => (new \DateTime('-2 years'))->format('Y-m-d H:i:s'),
]);
$dataTable->addGroupBy('products.id');

$dataTable->addSelect();
$dataTable->addAction(function ($row) use ($dataTable) {
    return [
        $dataTable->createButton()
            ->setUrl('/button/' . $row['id'])
            ->setText('Button')
            ->setClasses(['btn', 'btn-primary', 'btn-sm']),
        $dataTable->createDropdown([
            $dataTable->createButton()
                ->setUrl('/button/' . $row['id'])
                ->setText('Button'),
            $dataTable->createButton()
                ->setUrl('/button/' . $row['id'])
                ->setText('Button'),
        ]),
    ];
});

$dataTable->addColumn('id');
$dataTable->addColumn('name');
$dataTable->addColumn('product_code');
$dataTable->addColumn('total_sales')->setQuery('SUM(line_items.quantity)')->setHeader('Country Name');
$dataTable->addColumn('created_at')->setFilterDateRange(true);

$dataTable->setDefaultOrder('name', 'asc');
$dataTable->setExportFileName('products-' . date('Y-m-d-His'));

$dataTable->addRowFormatter(function ($row) {
    return [
        'class' => 'text-danger',
    ];
});

$request = Request::createFromGlobals();

if ($dataTable->process($request)) {
    return $dataTable->sendResponse();
}

require_once __DIR__ . '/includes/header.php';
echo $dataTable->render();
require_once __DIR__ . '/includes/footer.php';
