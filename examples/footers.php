<?php

use Rhino\DataTable\Footer;
use Rhino\DataTable\MySqlDataTable;
use Rhino\DataTable\MySqlFooter;
use Rhino\DataTable\Preset;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/includes/autoload.php';

$pdo = require_once __DIR__ . '/includes/pdo.php';

$dataTable = new MySqlDataTable($pdo, 'line_items');
$dataTable->setDebug(true);
$dataTable->addJoin('LEFT JOIN products ON products.id = line_items.product_id');
$dataTable->addGroupBy('line_items.id');

$dataTable->addColumn('id')->setFooter(new Footer(['Page total:', 'Table total:']));
$dataTable->addColumn('quantity')->addPreset(new Preset\Number())->setFooter(new MySqlFooter('SUM(quantity)'));
$dataTable->addColumn('product_unit_price')->setQuery('products.unit_price')->addPreset(new Preset\Money())->setFooter(new MySqlFooter('SUM(product_unit_price)'));

$request = Request::createFromGlobals();

if ($dataTable->process($request)) {
    return $dataTable->sendResponse();
}

require_once __DIR__ . '/includes/header.php';
echo $dataTable->render();
require_once __DIR__ . '/includes/footer.php';
