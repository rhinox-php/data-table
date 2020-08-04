<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Rhino\DataTable\InputData;
use Symfony\Component\HttpFoundation\Request;

class ArrayDataTableTest extends \PHPUnit\Framework\TestCase
{
    public function testIndexedArray(): void
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

        $json = $this->getJsonResponse([], $dataTable);
        $this->assertCount(4, $json['data']);
    }

    // public function testObjectArray(): void
    // {
    //     $dataTable = new ArrayDataTable([
    //         (object) ['i' => 1, 'color' => 'red', 'choice' => 'yes'],
    //         (object) ['i' => 2, 'color' => 'blue', 'choice' => 'no'],
    //         (object) ['i' => 3, 'color' => 'blue', 'choice' => 'yes'],
    //         (object) ['i' => 4, 'color' => null, 'choice' => 'no'],
    //     ]);

    //     $dataTable->addColumn('i')->setProperty('i');
    //     $dataTable->addColumn('color')->setProperty('color');
    //     $dataTable->addColumn('choice', 1)->setProperty('choice');

    //     $json = $this->getJsonResponse([], $dataTable);
    //     $this->assertCount(4, $json['data']);
    // }

    public function testClassArray(): void
    {
        $makeClass = function ($i, $color, $choice) {
            return new class ($i, $color, $choice)
            {
                private $i;
                public $color;
                public $choice;

                public function __construct($i, $color, $choice)
                {
                    $this->i = $i;
                    $this->color = $color;
                    $this->choice = $choice;
                }

                public function getI()
                {
                    return $this->i;
                }

                public function getColor()
                {
                    return $this->color;
                }

                public function getChoice()
                {
                    return $this->choice;
                }
            };
        };

        $dataTable = new ArrayDataTable([
            $makeClass(1, 'red', 'yes'),
            $makeClass(2, 'green', 'no'),
            $makeClass(3, 'blue', 'yes'),
            $makeClass(4, null, 'no'),
        ]);

        $dataTable->addColumn('i')->setMethod('getI');
        $dataTable->addColumn('color')->setProperty('color');
        $dataTable->addColumn('choice', 1)->setCallback(fn($row) => $row->choice);

        $json = $this->getJsonResponse([], $dataTable);
        $this->assertCount(4, $json['data']);
    }

    private function getJsonResponse(array $requestParams, ?ArrayDataTable $dataTable = null): InputData
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


    private function getDataTable(): ArrayDataTable
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

        return $dataTable;
    }
}
