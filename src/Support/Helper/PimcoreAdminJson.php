<?php

namespace Dachcom\Codeception\Support\Helper;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Dachcom\Codeception\Support\Constraint\JsonContains;
use PHPUnit\Framework\Assert;

class PimcoreAdminJson extends Module implements DependsOnModule
{
    protected PimcoreCore $connectionModule;

    public function _depends(): array
    {
        return [PimcoreCore::class => 'PimcoreAdminJson needs a valid browser to work.'];
    }

    public function _inject(PimcoreCore $connection): void
    {
        $this->connectionModule = $connection;
    }

    /**
     * Actor Function to see response contains csv
     */
    public function seeResponseContainsJson(array $json = []): void
    {
        Assert::assertThat(
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
        Assert::assertNotEquals('', $responseContent, 'response is empty');
        json_decode($responseContent);
        $errorCode = json_last_error();
        $errorMessage = json_last_error_msg();
        Assert::assertEquals(
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
