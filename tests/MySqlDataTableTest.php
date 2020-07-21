<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\InputData;
use Rhino\DataTable\MySqlDataTable;
use Symfony\Component\HttpFoundation\Request;

class MySqlDataTableTest extends \PHPUnit\Framework\TestCase
{
    public function testRender(): void
    {
        $dataTable = $this->getDataTable();
        $html = $dataTable->render();
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    public function testJsonResponse(): void
    {
        $json = $this->getJsonResponse([]);
        $this->assertCount(10, $json['data']);
    }

    public function testOrder(): void
    {
        $json = $this->getJsonResponse([
            'draw' => 1,
            'json' => true,
            'order' => [[
                'column' => 5,
                'dir' => 'asc',
            ]],
        ]);
        $this->assertCount(10, $json['data']);
        $totalQuantity = $json->arr('data')->map(function ($row) {
            return $row->int('totalQuantity');
        })->getData();
        $sortedTotalQuantity = $totalQuantity;
        sort($sortedTotalQuantity);
        $this->assertEquals($sortedTotalQuantity, $totalQuantity);
    }

    public function testColumnSearch(): void
    {
        $json = $this->getJsonResponse([
            'draw' => 1,
            'json' => true,
            'columns' => [
                3 => [
                    'search' => [
                        'value' => 'accusantium',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('accusantium', $row['name']);
        }
    }

    public function testGlobalSearch(): void
    {
        $json = $this->getJsonResponse([
            'draw' => 1,
            'json' => true,
            'search' => [
                'value' => 'accusantium',
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('accusantium', implode(',', $row->arr()->getData()));
        }
    }

    private function getJsonResponse(array $requestParams): InputData
    {

        $dataTable = $this->getDataTable();
        $request = new Request([], array_merge([
            'draw' => 1,
            'json' => true,
        ], $requestParams));

        $this->assertTrue($dataTable->process($request));

        ob_start();
        $dataTable->sendResponse();
        $response = ob_get_clean();
        return InputData::jsonDecode($response);
    }

    private function getDataTable(): MySqlDataTable
    {
        $pdo = new \PDO('mysql:host=localhost;dbname=rhino_data_table_examples', 'root', 'root', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;',
        ]);
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
        $dataTable->addColumn('code');
        $dataTable->addColumn('total_quantity')->setQuery('SUM(line_items.quantity)')->setHeader('Total Quantity')->setPreset('number');
        $dataTable->addColumn('total_sales')->setQuery('SUM(line_items.quantity * products.unit_price)')->setHeader('Total Sales')->setPreset('money');
        $dataTable->insertColumn('random', function () {
            return rand(0, 100);
        });
        $dataTable->addColumn('created_at')->setFilterDateRange(true);

        $dataTable->setDefaultOrder('name', 'asc');
        $dataTable->setExportFileName('products-' . date('Y-m-d-His'));

        $dataTable->addRowFormatter(function ($row) {
            return [
                'class' => $row['random'] < 30 ? 'text-danger' : null,
            ];
        });

        return $dataTable;
    }
}
