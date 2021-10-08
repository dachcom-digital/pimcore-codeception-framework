<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Lib\InnerBrowser;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Dachcom\Codeception\Constraint\JsonContains;

class PimcoreAdminJson extends Module implements DependsOnModule
{
    protected InnerBrowser $connectionModule;

    public function _depends(): array
    {
        return [InnerBrowser::class => 'PimcoreAdminJson needs a valid browser to work.'];
    }

    public function _inject(InnerBrowser $connection): void
    {
        $this->connectionModule = $connection;
    }

    /**
     * Actor Function to see response contains csv
     */
    public function seeResponseContainsJson(array $json = []): void
    {
        \PHPUnit_Framework_Assert::assertThat(
            $this->connectionModule->_getResponseContent(),
            new JsonContains($json)
        );
    }

    /**
     * Actor Function to see response is json
     */
    public function seeResponseIsJson(): void
    {
        $responseContent = $this->connectionModule->_getResponseContent();
        \PHPUnit_Framework_Assert::assertNotEquals('', $responseContent, 'response is empty');
        json_decode($responseContent);
        $errorCode = json_last_error();
        $errorMessage = json_last_error_msg();
        \PHPUnit_Framework_Assert::assertEquals(
            JSON_ERROR_NONE,
            $errorCode,
            sprintf(
                "Invalid json: %s. System message: %s.",
                $responseContent,
                $errorMessage
            )
        );
    }
}
