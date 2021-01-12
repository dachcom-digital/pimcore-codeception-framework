<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Lib\InnerBrowser;
use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\PHPUnit\Constraint\JsonContains;

class PimcoreAdminJson extends Module implements DependsOnModule
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
        return ['Codeception\Lib\InnerBrowser' => 'PimcoreAdminJson needs a valid browser to work.'];
    }

    /**
     * @param InnerBrowser $connection
     */
    public function _inject(InnerBrowser $connection)
    {
        $this->connectionModule = $connection;
    }

    /**
     * Actor Function to see response contains csv
     *
     * @param array $json
     *
     * @throws ModuleException
     */
    public function seeResponseContainsJson($json = [])
    {
        \PHPUnit_Framework_Assert::assertThat(
            $this->connectionModule->_getResponseContent(),
            new JsonContains($json)
        );
    }

    /**
     * Actor Function to see response is json
     *
     * @throws ModuleException
     */
    public function seeResponseIsJson()
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
