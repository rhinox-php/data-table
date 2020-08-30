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
        $json = $this->getJsonResponse([], $dataTable, false);
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
        $this->assertEquals('-', $json->string('data.0.value'));
        $this->assertEquals('-', $json->string('data.1.value'));
        $this->assertEquals('Yes', $json->string('data.2.value'));
        $this->assertEquals('Yes', $json->string('data.3.value'));
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
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Bytes());
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('100 B', $json->string('data.0.value'));
        $this->assertEquals('100 KB', $json->string('data.1.value'));
        $this->assertEquals('100 MB', $json->string('data.2.value'));
        $this->assertEquals('100 GB', $json->string('data.3.value'));
        $this->assertEquals('100 TB', $json->string('data.4.value'));
        $this->assertEquals('100 PB', $json->string('data.5.value'));
        $this->assertEquals('102400 PB', $json->string('data.6.value'));

        $dataTable = new ArrayDataTable([
            [100],
            [100 * 1024],
            [100 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024 * 1024 * 1024],
            [100 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset((new Preset\Bytes())->setUnits(Preset\Bytes::LONG_UNITS));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('100 Bytes', $json->string('data.0.value'));
        $this->assertEquals('100 Kilobytes', $json->string('data.1.value'));
        $this->assertEquals('100 Megabytes', $json->string('data.2.value'));
        $this->assertEquals('100 Gigabytes', $json->string('data.3.value'));
        $this->assertEquals('100 Terabytes', $json->string('data.4.value'));
        $this->assertEquals('100 Petabytes', $json->string('data.5.value'));
        $this->assertEquals('102400 Petabytes', $json->string('data.6.value'));
    }

    public function testDate(): void
    {
        // @todo test custom format with sorting
        $now1 = new \DateTime();
        $now2 = new \DateTimeImmutable();
        $dataTable = new ArrayDataTable([
            [$now1],
            [$now2],
            [null],
            ['invalid date'],
            ['2019-09-24'],
            ['2019-07-24'],
            ['2019-08-24'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset((new Preset\Date())->setTimeZone('UTC'));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('', $json->string('data.0.value'));
        $this->assertEquals('2019-07-24', $json->string('data.1.value'));
        $this->assertEquals('2019-08-24', $json->string('data.2.value'));
        $this->assertEquals('2019-09-24', $json->string('data.3.value'));
        $this->assertEquals($now1->format('Y-m-d'), $json->string('data.4.value'));
        $this->assertEquals($now2->format('Y-m-d'), $json->string('data.5.value'));
        $this->assertEquals('invalid date', $json->string('data.6.value'));
    }

    public function testDateTime(): void
    {
        // @todo test custom format with sorting
        $now1 = new \DateTime();
        $now2 = new \DateTimeImmutable();
        $dataTable = new ArrayDataTable([
            [$now1],
            [$now2],
            [null],
            ['invalid date'],
            ['2019-09-24 01:02:03'],
            ['2019-07-24 13:14:15'],
            ['2019-08-24 23:59:59'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset((new Preset\DateTime())->setTimeZone('UTC'));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('', $json->string('data.0.value'));
        $this->assertEquals('2019-07-24 13:14:15', $json->string('data.1.value'));
        $this->assertEquals('2019-08-24 23:59:59', $json->string('data.2.value'));
        $this->assertEquals('2019-09-24 01:02:03', $json->string('data.3.value'));
        $this->assertEquals($now1->format('Y-m-d H:i:s'), $json->string('data.4.value'));
        $this->assertEquals($now2->format('Y-m-d H:i:s'), $json->string('data.5.value'));
        $this->assertEquals('invalid date', $json->string('data.6.value'));
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
        $this->assertEquals('', $json->string('data.0.value'));
        $this->assertEquals('baz', $json->string('data.1.value'));
        // Note the sort is done before formatting
        $this->assertEquals('Bar', $json->string('data.2.value'));
    }

    public function testGroup(): void
    {
        $dataTable = new ArrayDataTable([
            ['a,b,c,d'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset((new Preset\Group())->setLimit(3));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('a, b, c...', $json->string('data.0.value'));
    }

    public function testHtml(): void
    {
        $dataTable = new ArrayDataTable([
            ['<b>Foo</b>'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Html());
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('<b>Foo</b>', $json->string('data.0.value'));
    }

    public function testHuman(): void
    {
        $dataTable = new ArrayDataTable([
            ['abc-def'],
            ['abc_def'],
            ['abc,def'],
            [null],
        ]);
        // @todo should this handle camel case
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Human());
        $json = $this->getJsonResponse([], $dataTable);
        // @todo need to fix sort order
        $this->assertEquals('', $json->string('data.0.value'));
        $this->assertEquals('Abc, Def', $json->string('data.1.value'));
        $this->assertEquals('Abc Def', $json->string('data.2.value'));
        $this->assertEquals('Abc Def', $json->string('data.3.value'));
    }

    public function testJsonArray(): void
    {
        $dataTable = new ArrayDataTable([
            ['[2,1,3]'],
            [null],
            ['{'],
            ['[]'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonArray());
        $json = $this->getJsonResponse([], $dataTable, false);
        $this->assertStringContainsString('<li>1</li><li>2</li><li>3</li>', $json->string('data.0.value'));
        $this->assertSame('', $json->string('data.1.value'));
        $this->assertSame('{', $json->string('data.2.value'));
        $this->assertSame('', $json->string('data.3.value'));

        $csv = $this->getJsonResponse([
            'csv' => true,
        ], $dataTable, false);
        $this->assertStringContainsString('1, 2, 3', $csv);
    }

    public function testJsonArrayKey(): void
    {
        $dataTable = new ArrayDataTable([
            ['{"foo":true,"bar":false,"baz":true}'],
            [null],
            ['{'],
            ['[]'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonArrayKey());
        $json = $this->getJsonResponse([], $dataTable, false);
        $this->assertStringContainsString('<li>baz</li><li>foo</li>', $json->string('data.0.value'));
        $this->assertStringNotContainsString('<li>bar</li>', $json->string('data.0.value'));
        $this->assertSame('', $json->string('data.1.value'));
        $this->assertSame('{', $json->string('data.2.value'));
        $this->assertSame('', $json->string('data.3.value'));

        $csv = $this->getJsonResponse([
            'csv' => true,
        ], $dataTable, false);
        $this->assertStringContainsString('baz, foo', $csv);
        $this->assertStringNotContainsString('bar', $csv);
    }

    public function testJsonObject(): void
    {
        $dataTable = new ArrayDataTable([
            ['{"foo":"bar"}'],
            [null],
            ['{'],
            ['[]'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonObject());
        $json = $this->getJsonResponse([], $dataTable, false);
        $this->assertStringContainsString('<li><b>foo:</b> bar</li>', $json->string('data.0.value'));
        $this->assertSame('', $json->string('data.1.value'));
        $this->assertSame('{', $json->string('data.2.value'));
        $this->assertSame('', $json->string('data.3.value'));

        $csv = $this->getJsonResponse([
            'csv' => true,
        ], $dataTable, false);
        $this->assertStringContainsString('foo: bar', $csv);
    }

    public function testJsonString(): void
    {
        $dataTable = new ArrayDataTable([
            ['"test"'],
            ['"broken'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\JsonString());
        $json = $this->getJsonResponse([], $dataTable, false);
        $this->assertSame('test', $json->string('data.0.value'));
        $this->assertSame('"broken', $json->string('data.1.value'));
    }

    public function testLink(): void
    {
        $dataTable = new ArrayDataTable([
            ['123', 'foo'],
        ]);
        $dataTable->addColumn('value1')->setIndex(0)->addPreset(new Preset\Link('/foo/bar/{value1}/{value2}'));
        $dataTable->addColumn('value2')->setIndex(1)->addPreset(new Preset\Link('/foo/bar/{value3}'));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertSame('<a href="/foo/bar/123/foo">123</a>', $json->string('data.0.value1'));
        $this->assertSame('<a href="/foo/bar/{value3}">foo</a>', $json->string('data.0.value2'));
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
        $this->assertEquals('', $json->string('data.0.value'));
        $this->assertEquals('$ 123.00', $json->string('data.1.value'));
        $this->assertEquals('$ 1,234,567,890.00', $json->string('data.2.value'));
        $this->assertEquals('$ 9,876,543,210.00', $json->string('data.3.value'));
    }

    public function testNumber(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Number());
        $json = $this->getJsonResponse([], $dataTable);
    }

    public function testPercent(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
            ['7.653'],
            ['1000'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset((new Preset\Percent())->setDecimalPlaces(2));
        $json = $this->getJsonResponse([], $dataTable, false);
        $this->assertEquals('123.00 %', $json->string('data.0.value'));
        $this->assertEquals('7.65 %', $json->string('data.1.value'));
        $this->assertEquals('1,000.00 %', $json->string('data.2.value'));
    }

    public function testPrefix(): void
    {

        $dataTable = new ArrayDataTable([
            ['123'],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Prefix('P: '));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('', $json->string('data.0.value'));
        $this->assertEquals('P: 123', $json->string('data.1.value'));
    }

    public function testSuffix(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
            [null],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset(new Preset\Suffix('-S'));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('', $json->string('data.0.value'));
        $this->assertEquals('123-S', $json->string('data.1.value'));
    }

    public function testTruncate(): void
    {
        $dataTable = new ArrayDataTable([
            ['123'],
            ['12345'],
        ]);
        $dataTable->addColumn('value')->setIndex(0)->addPreset((new Preset\Truncate())->setMaxLength(3));
        $json = $this->getJsonResponse([], $dataTable);
        $this->assertEquals('123', $json->string('data.0.value'));
        $this->assertEquals('123...', $json->string('data.1.value'));
    }

    /**
     * @return InputData|string
     */
    private function getJsonResponse(array $requestParams, ArrayDataTable $dataTable = null, bool $sorted = true)
    {
        if (!$sorted) {
            $dataTable->setDefaultOrder(null);
        }
        $request = new Request([], array_merge([
            'draw' => 1,
            'json' => true,
            'order' => $sorted ? [[
                'column' => 0,
                'dir' => 'asc',
            ]] : null,
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
}
