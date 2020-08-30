<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Rhino\InputData\InputData;
use Symfony\Component\HttpFoundation\Request;

class ArrayDataTableTest extends \PHPUnit\Framework\TestCase
{
    public function testRender(): void
    {
        $dataTable = $this->getDataTable();
        $this->assertFalse($dataTable->process(new Request()));
        $html = $dataTable->render();
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
    }

    public function testSetId(): void
    {
        $dataTable = $this->getDataTable();
        $dataTable->setId('test-table');
        $this->assertFalse($dataTable->process(new Request()));
        $html = $dataTable->render();
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('id="test-table"', $html);
        $this->assertStringContainsString('</table>', $html);
    }

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

    public function testObjectArray(): void
    {
        $dataTable = new ArrayDataTable([
            (object) ['i' => 1, 'color' => 'red', 'choice' => 'yes'],
            (object) ['i' => 2, 'color' => 'blue', 'choice' => 'no'],
            (object) ['i' => 3, 'color' => 'blue', 'choice' => 'yes'],
            (object) ['i' => 4, 'color' => null, 'choice' => 'no'],
        ]);

        $dataTable->addColumn('i')->setProperty('i');
        $dataTable->addColumn('color')->setProperty('color');
        $dataTable->addColumn('choice', 1)->setProperty('choice');

        $json = $this->getJsonResponse([], $dataTable);
        $this->assertCount(4, $json['data']);
    }

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
        $dataTable->addColumn('choice', 1)->setCallback(fn ($row) => $row->choice);

        $json = $this->getJsonResponse([], $dataTable);
        $this->assertCount(4, $json['data']);
    }

    /**
     * @dataProvider provideSort
     */
    public function testSort(array $rows, array $expectedValues, string $direction)
    {
        $dataTable = new ArrayDataTable($rows);
        $dataTable->addColumn('value')->setIndex(0);
        $json = $this->getJsonResponse([
            'order' => [[
                'column' => 0,
                'dir' => $direction,
            ]],
        ], $dataTable);
        foreach ($expectedValues as $i => $expectedValue) {
            $this->assertEquals($expectedValue, $json->arr('data')->arr($i)->string('value'));
        }
    }

    public function provideSort()
    {
        return [
            [[[2], [1], [3]], [1, 2, 3], 'asc'],
            [[[2], [1], [3]], [3, 2, 1], 'desc'],
            [[[['foo' => 1]], [['bar' => 2]]], [
                htmlspecialchars(json_encode(['bar' => 2]), ENT_QUOTES, 'UTF-8', false),
                htmlspecialchars(json_encode(['foo' => 1]), ENT_QUOTES, 'UTF-8', false),
            ], 'asc'],
        ];
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
