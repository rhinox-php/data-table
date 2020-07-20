<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Symfony\Component\HttpFoundation\Request;

class PaginationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider providePagination
     */
    public function testPagination(?int $start, ?int $length, array $expected, int $expectedCount): void
    {
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = [$i, $i * 2, $i * 3];
        }
        $dataTable = new ArrayDataTable($data);

        $dataTable->addColumn('i')->setIndex(0);
        $dataTable->addColumn('i2')->setIndex(1);
        $dataTable->addColumn('i3')->setIndex(2);

        $dataTable->process(new Request([], [
            'draw' => 1,
            'json' => true,
            'order' => [[
                'column' => 0,
                'dir' => 'asc',
            ]],
            'start' => $start,
            'length' => $length,
        ]));
        $responseData = $dataTable->getJsonResponseData();
        $this->assertCount($expectedCount, $responseData['data']);
        foreach ($expected as $i => $expectedValue) {
            $this->assertEquals($expectedValue, $responseData['data'][$i]['i']);
        }
    }

    public function providePagination()
    {
        return [
            [
                'start' => 3,
                'length' => 3,
                'expected' => [3, 4, 5],
                'expectedCount' => 3,
            ],
            [
                'start' => 0,
                'length' => 5,
                'expected' => [0, 1, 2, 3, 4],
                'expectedCount' => 5,
            ],
            // Empty start, default to 0
            [
                'start' => null,
                'length' => 3,
                'expected' => [0, 1, 2],
                'expectedCount' => 3,
            ],
            // Empty length, default to 10
            [
                'start' => 5,
                'length' => null,
                'expected' => [5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
                'expectedCount' => 10,
            ],
            // Empty start and length, return first 10
            [
                'start' => null,
                'length' => null,
                'expected' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                'expectedCount' => 10,
            ],
            // Out of range, return nothing
            [
                'start' => 100,
                'length' => 100,
                'expected' => [],
                'expectedCount' => 0,
            ],
            // No limit
            [
                'start' => 0,
                'length' => -1,
                'expected' => range(0, 99),
                'expectedCount' => 100,
            ],
        ];
    }
}
