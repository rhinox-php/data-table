<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Symfony\Component\HttpFoundation\Request;

class GlobalFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideGlobalFilter
     */
    public function testGlobalFilter(?string $searchString, array $expected, int $expectedCount): void
    {
        $data = [
            [1, 'red', 'yes'],
            [2, 'green', 'no'],
            [3, 'blue', 'yes'],
            [4, null, 'no'],
        ];
        $dataTable = new ArrayDataTable($data);

        $dataTable->addColumn('i')->setIndex(0);
        $dataTable->addColumn('color')->setIndex(1);
        $dataTable->addColumn('choice')->setIndex(2);

        $dataTable->process(new Request([], [
            'draw' => 1,
            'json' => true,
            'order' => [[
                'column' => 0,
                'dir' => 'asc',
            ]],
            'search' => [
                'value' => $searchString,
            ],
        ]));
        $responseData = $dataTable->getJsonResponseData();
        $this->assertCount($expectedCount, $responseData['data']);
        foreach ($expected as $i => $expectedValue) {
            $this->assertEquals($expectedValue, $responseData['data'][$i]['i']);
        }
    }

    public function provideGlobalFilter()
    {
        return [
            [
                'search' => 'red',
                'expected' => [1],
                'expectedCount' => 1,
            ],
            [
                'search' => 'yes',
                'expected' => [1, 3],
                'expectedCount' => 2,
            ],
            [
                'search' => 'no',
                'expected' => [2, 4],
                'expectedCount' => 2,
            ],
            [
                'search' => 'not found',
                'expected' => [],
                'expectedCount' => 0,
            ],
        ];
    }
}
