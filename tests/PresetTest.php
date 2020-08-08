<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Rhino\DataTable\InputData;
use Rhino\DataTable\Preset;
use Symfony\Component\HttpFoundation\Request;

class PresetTest extends \PHPUnit\Framework\TestCase
{
    public function testBoolean(): void
    {
        $dataTable = new ArrayDataTable([
            [true],
            ['123'],
            [false],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Boolean());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo option values
        $this->assertEquals('Yes', $json->string('data.0.value'));
        $this->assertEquals('Yes', $json->string('data.1.value'));
        $this->assertEquals('-', $json->string('data.2.value'));
        $this->assertEquals('-', $json->string('data.3.value'));
    }

    public function testId(): void
    {
        $dataTable = new ArrayDataTable([
            ['1234567890'],
            ['9876543210'],
            ['123'],
            [null],
        ]);
        $dataTable->addColumn('id')->setIndex(0);
        $json = $this->getJsonResponse([], $dataTable);
        // @todo need to confirm what ID preset should do
    }

    public function testMoney(): void
    {
        $dataTable = new ArrayDataTable([
            ['9876543210'],
            ['1234567890'],
            ['123'],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Money());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo test class names
        // @todo currencies
        // @todo decimal places
        $this->assertEquals('$ 9,876,543,210.00', $json->string('data.0.value'));
        $this->assertEquals('$ 1,234,567,890.00', $json->string('data.1.value'));
        $this->assertEquals('$ 123.00', $json->string('data.2.value'));
        $this->assertEquals('', $json->string('data.3.value'));
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
