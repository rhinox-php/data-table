<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Symfony\Component\HttpFoundation\Request;

class RowFormatterTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $data = [
            [1, 'red'],
            [2, 'green'],
            [3, 'blue'],
            [4, null],
        ];
        $dataTable = new ArrayDataTable($data);

        $dataTable->addColumn('i')->setIndex(0);
        $dataTable->addColumn('color')->setIndex(1);

        $dataTable->addRowFormatter(function ($row) {
            return [
                'class' => $row['color'] ? 'bg-' . $row['color'] : null,
            ];
        });

        $dataTable->process(new Request([], [
            'draw' => 1,
            'json' => true,
            'order' => [[
                'column' => 0,
                'dir' => 'asc',
            ]],
        ]));
        $responseData = $dataTable->getJsonResponseData();
        $this->assertCount(4, $responseData['data']);
        foreach ($data as $i => $row) {
            if ($row[1]) {
                $this->assertArrayHasKey('DT_RowClass', $responseData['data'][$i]);
                $this->assertEquals('bg-' . $row[1], $responseData['data'][$i]['DT_RowClass']);
            } else {
                $this->assertArrayNotHasKey('DT_RowClass', $responseData['data'][$i]);
            }
        }
    }
}
