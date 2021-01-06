<?php

namespace Dachcom\Codeception\Helper\Browser;

use Codeception\Module;
use Dachcom\Codeception\Util\EditableHelper;
use Dachcom\Codeception\Util\FileGeneratorHelper;
use GuzzleHttp\Client;

class WebDriver extends Module\WebDriver
{
    /**
     * Actor Function to see a page with enabled edit-mode
     *
     * @param string $page
     */
    public function amOnPageInEditMode(string $page)
    {
        $this->amOnPage(sprintf('%s?pimcore_editmode=true', $page));
    }

    /**
     * @param null $path
     */
    public function setDownloadPathForWebDriver($path = null)
    {
        if (is_null($path)) {
            $path = FileGeneratorHelper::getWebdriverDownloadPath();
        }

        $url = $this->webDriver->getCommandExecutor()->getAddressOfRemoteServer();
        $uri = sprintf('/session/%s/chromium/send_command', $this->webDriver->getSessionID());

        $body = [
            'cmd'    => 'Page.setDownloadBehavior',
            'params' => ['behavior' => 'allow', 'downloadPath' => $path]
        ];

        $client = new Client();
        $response = $client->post($url . $uri, ['body' => json_encode($body)]);

        try {
            $responseData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $responseData = [];
        }

        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals(0, $responseData['status']);
    }

    /**
     * @param string $name
     * @param string $type
     * @param array  $options
     * @param null   $data
     * @param null   $selector
     */
    public function seeAEditableConfiguration(string $name, string $type, array $options, $data = null, $selector = null)
    {
        $this->see(EditableHelper::generateEditableConfiguration($name, $type, $options, $data), $selector);
    }
}
