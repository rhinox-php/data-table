<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Symfony\Component\HttpFoundation\Request;

class ArrayDataTableTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $data = [
            [1, 'red', 'yes'],
            [2, 'green', 'no'],
            [3, 'blue', 'yes'],
            [4, null, 'no'],
        ];
        $dataTable = new ArrayDataTable([]);
        $dataTable->setArray($data);

        $dataTable->addColumn('i')->setIndex(0);
        $dataTable->addColumn('color')->setIndex(1);
        $dataTable->addColumn('choice', 1)->setIndex(2);

        $dataTable->process(new Request([], [
            'draw' => 1,
            'json' => true,
        ]));
        $responseData = $dataTable->getJsonResponseData();
        $this->assertCount(4, $responseData['data']);
    }
}
