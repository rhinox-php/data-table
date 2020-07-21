<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\Exception\QueryException;
use Rhino\DataTable\InputData;
use Rhino\DataTable\MySqlDataTable;
use Symfony\Component\HttpFoundation\Request;

// @todo test url filters http://localhost:8990/examples/kitchen-sink.php?filter[name]=acc
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
                'column' => 7,
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

    public function testGlobalSearch(): void
    {
        $json = $this->getJsonResponse([
            'draw' => 1,
            'json' => true,
            'search' => [
                'value' => 'mbp_15_retina_mid_15',
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_15_retina_mid_15', implode(',', $row->arr()->getData()));
        }
    }

    public function testColumnSearch(): void
    {
        $json = $this->getJsonResponse([
            'draw' => 1,
            'json' => true,
            'columns' => [
                4 => [
                    'search' => [
                        'value' => 'mbp_15_retina_mid_15',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_15_retina_mid_15', $row->string('code'));
        }
    }

    public function testMultiColumnSearch(): void
    {
        $json = $this->getJsonResponse([
            'draw' => 1,
            'json' => true,
            'columns' => [
                4 => [
                    'search' => [
                        'value' => 'mbp_13',
                    ],
                ],
                6 => [
                    'search' => [
                        'value' => '1499',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_13', $row->string('code'));
            $this->assertStringContainsStringIgnoringCase('1499', $row->string('unitPrice'));
        }
    }

    public function testGlobalAndColumnSearch(): void
    {
        $json = $this->getJsonResponse([
            'draw' => 1,
            'json' => true,
            'search' => [
                'value' => '7',
            ],
            'columns' => [
                3 => [
                    'search' => [
                        'value' => 'a',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('a', $row->string('name'));
            $this->assertStringContainsStringIgnoringCase('7', implode(',', $row->arr()->getData()));
        }
    }

    public function testExtraHaving(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->addHaving('code LIKE :test', [
            ':test' => 'mbp_13%',
        ]);
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_13', $row->string('code'));
        }
    }

    public function testExtraHavingAndGlobalSearch(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->addHaving('code LIKE :test', [
            ':test' => 'mbp_13%',
        ]);
        $json = $this->getJsonResponse([
            'search' => [
                'value' => '1499',
            ],
        ], $dataTable);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_13', $row->string('code'));
            $this->assertStringContainsStringIgnoringCase('1499', implode(',', $row->arr()->getData()));
        }
    }

    public function testWhere(): void
    {
        $date = new \DateTime('2014-01-01');
        $dataTable = $this->getDataTable();
        $dataTable->addWhere('products.created_at > :date', [
            ':date' => $date->format('Y-m-d H:i:s'),
        ]);
        $json = $this->getJsonResponse([
            'order' => [[
                'column' => 8,
                'dir' => 'asc',
            ]],
        ], $dataTable);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertGreaterThan($date, $row->dateTime('createdAt'));
        }
    }

    public function testBindColumnQuery(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->addColumn('high_value')->setQuery('IF(products.unit_price > :limit, "High Value", "Standard")', [
            ':limit' => 1399,
        ]);
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertGreaterThan(0, count($json->arr('data')));
        $highValue = $json->arr('data')->map(fn ($row) => $row->string('highValue'))->getData();
        $this->assertContains('High Value', $highValue);
        $this->assertContains('Standard', $highValue);
    }

    public function testUrlFilters(): void
    {
        $json = $this->getJsonResponse([
            'filter' => [
                'code' => 'mbp_13',
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_13', $row->string('code'));
        }
    }

    public function testSelectFilter(): void
    {
        $json = $this->getJsonResponse([
            'columns' => [
                5 => [
                    'search' => [
                        'value' => 'laptop',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));

        $json = $this->getJsonResponse([
            'columns' => [
                5 => [
                    'search' => [
                        'value' => 'desktop',
                    ],
                ],
            ],
        ]);
        $this->assertEquals(0, count($json->arr('data')));
    }

    public function testDateRangeFilter(): void
    {
        $json = $this->getJsonResponse([
            'columns' => [
                10 => [
                    'search' => [
                        'value' => '2014-01-01 00:00:00 to 2016-01-01 00:00:00',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));

        $json = $this->getJsonResponse([
            'columns' => [
                10 => [
                    'search' => [
                        'value' => '2016-01-01 00:00 to 2014-01-01 00:00',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));

        $json = $this->getJsonResponse([
            'columns' => [
                10 => [
                    'search' => [
                        'value' => '2013-02-01',
                    ],
                ],
            ],
        ]);
        $this->assertGreaterThan(0, count($json->arr('data')));

        $json = $this->getJsonResponse([
            'columns' => [
                10 => [
                    'search' => [
                        'value' => 'invalid',
                    ],
                ],
            ],
        ]);
        $this->assertEquals(0, count($json->arr('data')));
    }

    public function testInvalidQuery(): void
    {
        $dataTable = new MySqlDataTable($this->getPdo([
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]), 'invalid');
        $this->expectException(QueryException::class);
        $this->getJsonResponse([], $dataTable);
    }

    private function getJsonResponse(array $requestParams, ?MySqlDataTable $dataTable = null): InputData
    {
        $dataTable = $dataTable ?: $this->getDataTable();
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

    private function getPdo(?array $options = null): \PDO
    {
        $options = $options ?? [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;',
        ];
        return new \PDO('mysql:host=localhost;dbname=rhino_data_table_examples', 'root', 'root', $options);
    }

    private function getDataTable(): MySqlDataTable
    {
        $dataTable = new MySqlDataTable($this->getPdo(), 'products');

        $dataTable->addJoin('LEFT JOIN line_items ON line_items.product_id = products.id');
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
