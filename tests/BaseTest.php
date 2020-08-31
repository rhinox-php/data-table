<?php

namespace Rhino\DataTable\Tests;

use Rhino\DataTable\ArrayDataTable;
use Rhino\DataTable\Column;
use Rhino\DataTable\DataTable;
use Rhino\DataTable\Preset;
use Rhino\InputData\InputData;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return InputData|string
     */
    protected function getResponse(array $requestParams, DataTable $dataTable, bool $sorted = true)
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
