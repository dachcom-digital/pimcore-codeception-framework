<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Lib\InnerBrowser;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;

class PimcoreAdminCsv extends Module implements DependsOnModule
{
    /**
     * @var InnerBrowser
     */
    protected $connectionModule;

    /**
     * @return array|mixed
     */
    public function _depends()
    {
        return ['Codeception\Lib\InnerBrowser' => 'PimcoreAdminCsv needs a valid browser to work.'];
    }

    /**
     * @param InnerBrowser $connection
     */
    public function _inject(InnerBrowser $connection)
    {
        $this->connectionModule = $connection;
    }

    /**
     * Actor Function to see values in csv response
     *
     * @param array $headerValues
     *
     * @throws ModuleException
     */
    public function seeResponseCsvHeaderHasValues(array $headerValues)
    {
        $responseContent = $this->connectionModule->_getResponseContent();

        $rows = [];
        foreach (str_getcsv($responseContent, "\n") as $row) {
            $rows[] = str_getcsv($row);
        }

        foreach ($headerValues as $value) {
            \PHPUnit_Framework_Assert::assertContains($value, $rows[0]);
        }
    }

    /**
     * Actor Function to see values in specific csv row of csv response
     *
     * @param int   $index
     * @param array $values
     *
     * @throws ModuleException
     */
    public function seeResponseCsvRowValues(int $index, array $values)
    {
        $responseContent = $this->connectionModule->_getResponseContent();

        $rows = [];
        foreach (str_getcsv($responseContent, "\n") as $row) {
            $rows[] = str_getcsv($row);
        }

        \PHPUnit_Framework_Assert::assertArrayHasKey($index, $rows, 'index not available in csv data');
        $data = $rows[$index];

        foreach ($values as $key => $value) {
            if (is_numeric($key)) {
                $csvValue = $data[$key];
            } else {
                // index of header
                $headerKey = array_search($key, $rows[0]);
                $csvValue = $data[$headerKey];
            }
            \PHPUnit_Framework_Assert::assertEquals($value, $csvValue);
        }
    }

    /**
     * Actor Function to see response csv length
     *
     * @param int $length
     *
     * @throws ModuleException
     */
    public function seeResponseCsvLength(int $length)
    {
        $responseContent = $this->connectionModule->_getResponseContent();

        $rows = [];
        foreach (str_getcsv($responseContent, "\n") as $row) {
            $rows[] = str_getcsv($row);
        }

        \PHPUnit_Framework_Assert::assertCount($length, $rows);
    }

    /**
     * Actor Function to see response is csv
     *
     * @throws ModuleException
     */
    public function seeResponseIsCsv()
    {
        $responseContent = $this->connectionModule->_getResponseContent();
        \PHPUnit_Framework_Assert::assertNotEquals('', $responseContent, 'response is empty');

        $data = str_getcsv($responseContent, "\n");
        \PHPUnit_Framework_Assert::assertInternalType('array', $data);
        \PHPUnit_Framework_Assert::assertGreaterThanOrEqual(1, count($data), 'csv data is empty');

        foreach ($data as $row) {
            \PHPUnit_Framework_Assert::assertInternalType('array', str_getcsv($row));
        }
    }
}
