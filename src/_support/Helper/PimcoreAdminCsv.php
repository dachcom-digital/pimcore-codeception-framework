<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Lib\InnerBrowser;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;

class PimcoreAdminCsv extends Module implements DependsOnModule
{
    protected InnerBrowser $connectionModule;

    public function _depends(): array
    {
        return [InnerBrowser::class => 'PimcoreAdminCsv needs a valid browser to work.'];
    }

    public function _inject(InnerBrowser $connection): void
    {
        $this->connectionModule = $connection;
    }

    /**
     * Actor Function to see values in csv response
     */
    public function seeResponseCsvHeaderHasValues(array $headerValues): void
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
     */
    public function seeResponseCsvRowValues(int $index, array $values): void
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
     */
    public function seeResponseCsvLength(int $length): void
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
     */
    public function seeResponseIsCsv(): void
    {
        $responseContent = $this->connectionModule->_getResponseContent();
        \PHPUnit_Framework_Assert::assertNotEquals('', $responseContent, 'response is empty');

        $data = str_getcsv($responseContent, "\n");
        \PHPUnit_Framework_Assert::assertIsArray($data);
        \PHPUnit_Framework_Assert::assertGreaterThanOrEqual(1, count($data), 'csv data is empty');

        foreach ($data as $row) {
            \PHPUnit_Framework_Assert::assertIsArray(str_getcsv($row));
        }
    }
}
