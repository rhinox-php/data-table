<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Rhino\DataTable\Preset;
use Rhino\InputData\InputData;
use Symfony\Component\HttpFoundation\Request;

class PresetTest extends \PHPUnit\Framework\TestCase
{
    public function testArray(): void
    {
        $dataTable = new ArrayDataTable([
            [[4, 2, 3]],
            [5],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\ArrayList());
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertStringContainsString('<li>2</li><li>3</li><li>4</li>', $json->string('data.0.value'));
        $this->assertStringContainsString('<ul', $json->string('data.0.value'));
        $this->assertStringContainsString('</ul>', $json->string('data.0.value'));
        $this->assertStringContainsString('<li>5</li>', $json->string('data.1.value'));

        $dataTable = new ArrayDataTable([
            [[4, 2, 3]],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset((new Preset\ArrayList())->setSortFunction(fn ($a, $b) => $b - $a));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertStringContainsString('<li>4</li><li>3</li><li>2</li>', $json->string('data.0.value'));

        $dataTable = new ArrayDataTable([
            [[4, 2, 3]],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\ArrayList());
        $csv = $this->getJsonResponse([
            'csv' => true,
        ], $dataTable);
        $this->assertStringContainsString('2, 3, 4', $csv);
    }

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

    public function testBytes(): void
    {
        $dataTable = new ArrayDataTable([
            [100],
            [100 * 1024],
            [100 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Bytes());
        $json = $this->getJsonResponse([
            'order' => [[
                'column' => 0,
                'dir' => 'asc',
            ]],
        ], $dataTable);
        $this->assertEquals('100 B', $json->string('data.0.value'));
        $this->assertEquals('100 KB', $json->string('data.1.value'));
        $this->assertEquals('100 MB', $json->string('data.2.value'));
        $this->assertEquals('100 GB', $json->string('data.3.value'));
        $this->assertEquals('100 TB', $json->string('data.4.value'));
        $this->assertEquals('100 PB', $json->string('data.5.value'));
        $this->assertEquals('100 EB', $json->string('data.6.value'));
        $this->assertEquals('102400 EB', $json->string('data.7.value'));
    }

    public function testDate(): void
    {
        $now = new \DateTime();
        $dataTable = new ArrayDataTable([
            [$now],
            [null],
            ['invalid date'],
            ['2019-09-24'],
            ['2019-07-24'],
            ['2019-08-24'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Date());
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals($now->format('Y-m-d'), $json->string('data.0.value'));
        $this->assertEquals('2019-07-24', $json->string('data.1.value'));
        $this->assertEquals('2019-08-24', $json->string('data.2.value'));
        $this->assertEquals('2019-09-24', $json->string('data.3.value'));
        // @todo test custom format with sorting
    }

    public function testDateTime(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\DateTime());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testEnum(): void
    {
        $dataTable = new ArrayDataTable([
            ['foo'],
            ['baz'],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Enum([
            'foo' => 'Bar',
        ]));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('Bar', $json->string('data.0.value'));
        $this->assertEquals('baz', $json->string('data.1.value'));
        $this->assertEquals('', $json->string('data.2.value'));
    }

    public function testGroup(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Group());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testGroupLink(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\GroupLink());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testHtml(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Html());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testHuman(): void
    {
        $dataTable = new ArrayDataTable([
            ['abc-def'],
            ['abc_def'],
            [null],
        ]);
        // @todo should this handle camel case
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Human());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo need to fix sort order
        $this->assertEquals('Abc Def', $json->string('data.0.value'));
        $this->assertEquals('Abc Def', $json->string('data.1.value'));
        $this->assertEquals('', $json->string('data.2.value'));
    }

    public function testId(): void
    {
        $dataTable = new ArrayDataTable([
            ['1234567890'],
            ['9876543210'],
            ['123'],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Id());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo need to confirm what ID preset should do
        $this->markTestIncomplete();
    }

    public function testJsonArray(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonArray());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testJsonArrayKey(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonArrayKey());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testJsonObject(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonObject());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testJsonString(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonString());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testLength(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Length());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testLink(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Link());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
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

    public function testNumber(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Number());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    public function testPrefix(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Prefix('P: '));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('P: 123', $json->string('data.0.value'));
        $this->assertEquals('', $json->string('data.1.value'));
    }

    public function testSuffix(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Suffix('-S'));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('123-S', $json->string('data.0.value'));
        $this->assertEquals('', $json->string('data.1.value'));
    }

    public function testTrim(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Trim());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo complete test
        $this->markTestIncomplete();
    }

    /**
     * @return InputData|string
     */
    private function getJsonResponse(array $requestParams, ?ArrayDataTable $dataTable = null)
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
        if (isset($requestParams['csv'])) {
            return $response;
        }
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
