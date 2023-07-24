<?php

namespace Dachcom\Codeception\Support\Helper;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use PHPUnit\Framework\Assert;

class PimcoreAdminCsv extends Module implements DependsOnModule
{
    protected PimcoreCore $connectionModule;

    public function _depends(): array
    {
        return [PimcoreCore::class => 'PimcoreAdminCsv needs a valid browser to work.'];
    }

    public function _inject(PimcoreCore $connection): void
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
            Assert::assertContains($value, $rows[0]);
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

        Assert::assertArrayHasKey($index, $rows, 'index not available in csv data');
        $data = $rows[$index];

        foreach ($values as $key => $value) {
            if (is_numeric($key)) {
                $csvValue = $data[$key];
            } else {
                // index of header
                $headerKey = array_search($key, $rows[0]);
                $csvValue = $data[$headerKey];
            }
            Assert::assertEquals($value, $csvValue);
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

        Assert::assertCount($length, $rows);
    }

    /**
     * Actor Function to see response is csv
     */
    public function seeResponseIsCsv(): void
    {
        $responseContent = $this->connectionModule->_getResponseContent();
        Assert::assertNotEquals('', $responseContent, 'response is empty');

        $data = str_getcsv($responseContent, "\n");
        Assert::assertIsArray($data);
        Assert::assertGreaterThanOrEqual(1, count($data), 'csv data is empty');

        foreach ($data as $row) {
            Assert::assertIsArray(str_getcsv($row));
        }
    }
}
