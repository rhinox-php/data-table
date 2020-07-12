<?php

use Rhino\DataTable\MySqlDataTable;
use Symfony\Component\HttpFoundation\Request;


require_once __DIR__ . '/includes/autoload.php';

$pdo = require_once __DIR__ . '/includes/pdo.php';

$dataTable = new MySqlDataTable($pdo, 'country');
$dataTable->addJoin('LEFT JOIN city ON city.CountryCode = country.code');
$dataTable->addColumn('city_name')->setQuery('city.Name')->setHeader('City');
$dataTable->addColumn('code')->setHeader('Country Code');
$dataTable->addColumn('name')->setHeader('Country Name');
$dataTable->addColumn('continent');

$dataTable->setDefaultOrder('city_name', 'asc');

$request = Request::createFromGlobals();

if ($dataTable->process($request)) {
    return $dataTable->sendResponse();
}

require_once __DIR__ . '/includes/header.php';
echo $dataTable->render();
require_once __DIR__ . '/includes/footer.php';
