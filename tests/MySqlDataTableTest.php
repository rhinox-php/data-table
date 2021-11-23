<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\Exception\ConfigException;
use Rhino\DataTable\Exception\QueryException;
use Rhino\DataTable\MySqlDataTable;
use Rhino\DataTable\Preset;
use Rhino\InputData\MutableInputData;
use Symfony\Component\HttpFoundation\Request;

// @todo test url filters http://localhost:8990/examples/kitchen-sink.php?filter[name]=acc
class MySqlDataTableTest extends BaseTest
{
    public function testRender(): void
    {
        $dataTable = $this->getDataTable();
        $this->assertFalse($dataTable->process(new Request()));
        $html = $dataTable->render();
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    public function testGetters(): void
    {
        $dataTable = $this->getDataTable();
        $this->assertSame($dataTable, $dataTable->getColumn('code')->getDataTable());
        $this->assertNull($dataTable->getColumn('invalid_column'));
        $this->assertNull($dataTable->getColumnIndex('invalid_column'));
    }

    public function testAction(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->addAction(function ($row) use ($dataTable) {
            return 'Test static string';
        });
        $json = $this->getResponse([], $dataTable);
        $this->assertSame('Test static string', $json->string('data.0.action14'));
    }

    public function testButtons(): void
    {
        $json = $this->getResponse([], $this->getDataTable());
        $this->assertStringContainsString('button-attribute1="value1"', $json->string('data.0.action1'));
        $this->assertStringContainsString('button-attribute2="value2"', $json->string('data.0.action1'));
        $this->assertStringContainsString('<i class="fa fa-cog"></i>', $json->string('data.0.action1'));
        $this->assertStringNotContainsString('Hidden Button', $json->string('data.0.action1'));
        $this->assertStringContainsString('Link Button', $json->string('data.0.action1'));
        $this->assertStringContainsString('Submit Button', $json->string('data.0.action1'));
    }

    public function testJsonResponse(): void
    {
        $json = $this->getResponse([], $this->getDataTable());
        $this->assertCount(10, $json['data']);
    }

    public function testOrder(): void
    {
        $json = $this->getResponse([
            'draw' => 1,
            'json' => true,
            'order' => [[
                'column' => 7,
                'dir' => 'asc',
            ]],
        ], $this->getDataTable());
        $this->assertCount(10, $json['data']);
        $previous = 0;
        foreach ($json->arr('data') as $row) {
            $this->assertGreaterThanOrEqual($previous, $row->int('totalQuantity'));
            $previous = $row->int('totalQuantity');
        }
    }

    public function testInvalidOrder(): void
    {
        $dataTable = $this->getDataTable();
        $this->expectException(ConfigException::class);
        $dataTable->setDefaultOrder('foo', 'invalid');
    }

    public function testGlobalSearch(): void
    {
        $json = $this->getResponse([
            'draw' => 1,
            'json' => true,
            'search' => [
                'value' => 'mbp_15_retina_mid_15',
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_15_retina_mid_15', implode(',', $row->arr()->getData()));
        }
    }

    public function testColumnSearch(): void
    {
        $json = $this->getResponse([
            'draw' => 1,
            'json' => true,
            'columns' => [
                4 => [
                    'search' => [
                        'value' => 'mbp_15_retina_mid_15',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_15_retina_mid_15', $row->string('code'));
        }
    }

    public function testMultiColumnSearch(): void
    {
        $json = $this->getResponse([
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
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('mbp_13', $row->string('code'));
            $this->assertStringContainsStringIgnoringCase('1,499', $row->string('unitPrice'));
        }
    }

    public function testGlobalAndColumnSearch(): void
    {
        $json = $this->getResponse([
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
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsStringIgnoringCase('a', $row->string('name'));
            $this->assertStringContainsStringIgnoringCase('7', implode(',', $row->arr()->getData()));
        }
    }

    public function testNonSearchable(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->getColumn('code')->setSearchable(false);
        $json = $this->getResponse([
            'draw' => 1,
            'json' => true,
            'search' => [
                'value' => 'mbp_15_retina_mid_15',
            ],
        ], $dataTable);
        $this->assertCount(0, $json->arr('data'));
    }

    public function testNonSearchableColumnSearch(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->getColumn('code')->setSearchable(false);
        $json = $this->getResponse([
            'draw' => 1,
            'json' => true,
            'columns' => [
                4 => [
                    'search' => [
                        'value' => 'mbp_15_retina_mid_15',
                    ],
                ],
            ],
            'length' => 20,
        ], $dataTable);
        $this->assertCount(20, $json->arr('data'));
    }

    public function testExtraHaving(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->addHaving('code LIKE :test', [
            ':test' => 'mbp_13%',
        ]);
        $json = $this->getResponse([], $dataTable);
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
        $json = $this->getResponse([
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
        $json = $this->getResponse([
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
        $json = $this->getResponse([], $dataTable);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            if (str_replace(',', '', $row->string('unitPrice')) > 1399) {
                $this->assertSame('High Value', $row->string('highValue'));
            } else {
                $this->assertSame('Standard', $row->string('highValue'));
            }
        }
    }

    public function testUrlFilters(): void
    {
        $dataTable = $this->getDataTable();
        $this->getResponse([
            'filter' => [
                'code' => 'mbp_13',
            ],
        ], $dataTable);
        $this->assertSame('mbp_13', $dataTable->getColumn('code')->getDefaultColumnFilter());
    }

    public function testTextFilter(): void
    {
        $json = $this->getResponse([
            'columns' => [
                4 => [
                    'search' => [
                        'value' => 'mbp_13',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertStringContainsString('mbp_13', $row->string('code'));
        }

        $json = $this->getResponse([
            'columns' => [
                4 => [
                    'search' => [
                        'value' => '=mbp_13_retina_mid_14',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertEquals('mbp_13_retina_mid_14', $row->string('code'));
        }
    }

    public function testSelectFilter(): void
    {
        $json = $this->getResponse([
            'columns' => [
                5 => [
                    'search' => [
                        'value' => 'laptop',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));

        $json = $this->getResponse([
            'columns' => [
                5 => [
                    'search' => [
                        'value' => 'desktop',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertEquals(0, count($json->arr('data')));
    }

    public function testDateRangeFilterStartToFinish(): void
    {
        $json = $this->getResponse([
            'columns' => [
                11 => [
                    'search' => [
                        'value' => '2014-01-01 00:00:00 to 2016-01-01 00:00:00',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
    }

    public function testDateRangeFilterFinishToStart(): void
    {
        $json = $this->getResponse([
            'columns' => [
                11 => [
                    'search' => [
                        'value' => '2016-01-01 00:00 to 2014-01-01 00:00',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
    }

    public function testDateRangeFilterDate(): void
    {
        $json = $this->getResponse([
            'columns' => [
                11 => [
                    'search' => [
                        'value' => '2013-02-01',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertGreaterThan(0, count($json->arr('data')));
    }

    public function testDateRangeFilterInvalid(): void
    {
        $json = $this->getResponse([
            'columns' => [
                11 => [
                    'search' => [
                        'value' => 'invalid',
                    ],
                ],
            ],
        ], $this->getDataTable());
        $this->assertEquals(0, count($json->arr('data')));
    }

    /**
     * @dataProvider provideNumericFilter
     */
    public function testNumericFilter(string $filter, callable $check)
    {
        $dataTable = $this->getDataTable();
        $json = $this->getResponse([
            'columns' => [
                7 => [
                    'search' => [
                        'value' => $filter,
                    ],
                ],
            ],
        ], $dataTable);
        $this->assertGreaterThan(0, count($json->arr('data')));
        foreach ($json->arr('data') as $row) {
            $this->assertTrue($check($row->int('totalQuantity')));
        }
    }

    public function provideNumericFilter()
    {
        return [
            ['>508', fn ($v) => $v > 508],
            ['<508', fn ($v) => $v < 508],
            ['=508', fn ($v) => $v == 508],
            ['between 507 and 509', fn ($v) => $v == 508],
            ['between 509 and 507', fn ($v) => $v == 508],
            ['508', fn ($v) => $v == 508],
        ];
    }
    // int(631)
    // int(483)
    // int(508)
    public function testInvalidQuery(): void
    {
        $dataTable = new MySqlDataTable($this->getPdo([
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]), 'invalid_table');
        $dataTable->addColumn('invalid_column');
        $this->expectException(QueryException::class);
        $this->getResponse([], $dataTable);
    }

    public function testCsvExport(): void
    {
        $dataTable = $this->getDataTable();
        $request = new Request([], [
            'draw' => 1,
            'csv' => true,
        ]);

        $this->assertTrue($dataTable->process($request));

        ob_start();
        $dataTable->sendResponse();
        $response = trim(ob_get_clean());

        $handle = fopen("php://memory", 'r+');
        fputs($handle, $response);
        rewind($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $this->assertCount(11, $row);
        }
        fclose($handle);
    }

    public function testSetMeta(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->setMeta([
            'foo' => 'bar',
        ]);
        $json = $this->getResponse([], $dataTable);
        $this->assertSame('bar', $json->string('meta.foo'));
    }

    public function testSetUrl(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->setUrl('/another/url');
        $html = $dataTable->render();
        $this->assertStringContainsString('"url":"\/another\/url"', $html);
    }

    public function testDebug()
    {
        $dataTable = $this->getDataTable();
        $dataTable->addWhere('/* :test */ created_at > :date', [
            ':date' => (new \DateTime('1999-01-01'))->format('Y-m-d H:i:s'),
        ]);
        $dataTable->setDebug(true);
        $json = $this->getResponse([], $dataTable);
        $this->assertNotEmpty($json->arr('meta'));
        $this->assertGreaterThan(0, $json->decimal('meta.queryTime'));
        $this->assertStringContainsString('SELECT', $json->string('meta.sql'));
    }

    public function testNotDebug()
    {
        $dataTable = $this->getDataTable();
        $dataTable->setDebug(false);
        $json = $this->getResponse([], $dataTable);
        $this->assertEmpty($json->arr('meta'));
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

        $dataTable->addTableButton([
            'name' => 'test',
            'type' => 'button',
            'text' => 'Test',
            'class' => 'test',
            'confirm' => 'Are you sure you want to do this?',
        ]);

        $dataTable->addSelect();
        $dataTable->addAction(function ($row) use ($dataTable) {
            return [
                $dataTable->createButton()
                    ->setUrl('/button/' . $row['id'])
                    ->setText('Link Button')
                    ->setIcon('cog')
                    ->setAttributes([
                        'button-attribute1' => 'value1',
                    ])
                    ->addAttribute('button-attribute2', 'value2')
                    ->setClasses(['btn', 'btn-primary', 'btn-sm']),
                $dataTable->createButton()
                    ->setText('Hidden Button')
                    ->setVisible(false),
                $dataTable->createButton()
                    ->setText('Hidden Button')
                    ->setText('Submit Button')
                    ->setData([
                        'id' => $row['id'],
                    ]),
                $dataTable->createDropdown([
                    $dataTable->createButton()
                        ->setUrl('/button/delete')
                        ->setData([
                            'id' => $row['id'],
                        ])
                        ->setConfirmation('Are you sure you want to delete this?')
                        ->setTarget('_blank')
                        ->setClasses(['btn', 'btn-danger', 'btn-sm'])
                        ->setText('Delete'),
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
        $dataTable->addColumn('unit_price')->addFormatter(fn ($value) => number_format($value));
        $dataTable->addColumn('total_quantity')->setQuery('SUM(line_items.quantity)')->setHeader('Total Quantity')->addPreset(new Preset\Number());
        $dataTable->addColumn('total_sales')->setQuery('SUM(line_items.quantity * products.unit_price)')->setHeader('Total Sales')->addPreset(new Preset\Money());
        $dataTable->insertColumn('random', fn () => rand(0, 100))->setExportable(false);
        $dataTable->addColumn('updated_at')->setFilterDateRange(true);
        $dataTable->addColumn('created_at')->setQuery('DATE_FORMAT(products.created_at, "%M %Y")')->setOrderQuery('products.created_at')->setFilterQuery('created_at_filter')->setFilterDateRange(true);
        $dataTable->addColumn('deleted_at')->setVisible(false);
        $dataTable->addColumn('created_at_filter')->setQuery('products.created_at');

        $dataTable->setDefaultOrder('name', 'asc');
        $dataTable->setExportFileName('products-' . date('Y-m-d-His'));

        $dataTable->addRowFormatter(fn ($row) => [
            'class' => $row['random'] < 30 ? 'text-danger' : null,
        ]);

        return $dataTable;
    }
}
