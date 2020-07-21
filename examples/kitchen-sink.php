<?php

use Rhino\DataTable\MySqlDataTable;
use Symfony\Component\HttpFoundation\Request;


require_once __DIR__ . '/includes/autoload.php';

$pdo = require_once __DIR__ . '/includes/pdo.php';

$dataTable = new MySqlDataTable($pdo, 'products');
$dataTable->addJoin('LEFT JOIN line_items ON line_items.product_id = products.id');
// @todo where
// $dataTable->addWhere('products.created_at > :date', [
//     ':date' => (new \DateTime('-2 years'))->format('Y-m-d H:i:s'),
// ]);
$dataTable->addGroupBy('products.id');

// @todo advanced button
// @todo right align columns
// @todo different icon sets
// @todo drop down button shouldn't break to new line
// @todo select row header should be blank by default
// @todo varying row formatter color
// @todo download excel
// @todo csv/excel column formatters

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

$dataTable->addColumn('id')->setPreset('id')->setVisible(false);
$dataTable->addColumn('name');
$dataTable->addColumn('code');
$dataTable->addColumn('category')->setFilterSelect([
    'laptop' => [
        'category = :category',
        [
            ':category' => 'laptop',
        ],
    ],
    'desktop' => [
        'category = :category',
        [
            ':category' => 'desktop',
        ],
    ],
    'tablet' => [
        'category = :category',
        [
            ':category' => 'tablet',
        ],
    ],
    'phone' => [
        'category = :category',
        [
            ':category' => 'phone',
        ],
    ],
]);
$dataTable->addColumn('unit_price');
$dataTable->addColumn('total_quantity')->setQuery('SUM(line_items.quantity)')->setHeader('Total Quantity')->setPreset('number');
// @todo money format should not break line between $ sign
$dataTable->addColumn('total_sales')->setQuery('SUM(line_items.quantity * products.unit_price)')->setHeader('Total Sales')->setPreset('money');
$dataTable->insertColumn('random', function() {
    return rand(0, 100);
});
// @todo fix the date range UI
$dataTable->addColumn('created_at')->setQuery('DATE_FORMAT(products.created_at, "%M %Y")')->setOrderQuery('products.created_at')->setFilterDateRange(true);
// @todo date time preset as well as explicit date range filter

$dataTable->setDefaultOrder('name', 'asc');
$dataTable->setExportFileName('products-' . date('Y-m-d-His'));

$dataTable->addRowFormatter(function ($row) {
    return [
        'class' => $row['random'] < 30 ? 'text-danger' : null,
    ];
});

$request = Request::createFromGlobals();

if ($dataTable->process($request)) {
    return $dataTable->sendResponse();
}

require_once __DIR__ . '/includes/header.php';
echo $dataTable->render();
require_once __DIR__ . '/includes/footer.php';
