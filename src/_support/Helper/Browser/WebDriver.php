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
     * Actor Function to declare web driver download behaviour
     *
     * @param null $path
     */
    public function setDownloadPathForWebDriver($path = null)
    {
        if (is_null($path)) {
            $path = FileGeneratorHelper::getWebdriverDownloadPath();
        }

        $body = [
            'cmd'    => 'Page.setDownloadBehavior',
            'params' => ['behavior' => 'allow', 'downloadPath' => $path]
        ];

        $responseData = $this->sendWebDriverCommand($body);

        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals(0, $responseData['status']);
    }

    /**
     * Actor Function to clear web driver cache
     */
    public function clearWebDriverCache()
    {
        $body = [
            'cmd'    => 'Network.clearBrowserCache',
            'params' => ['params' => []]
        ];

        $responseData = $this->sendWebDriverCommand($body);

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

    /**
     * @param array $body
     *
     * @return array|mixed
     */
    protected function sendWebDriverCommand(array $body)
    {
        $url = $this->webDriver->getCommandExecutor()->getAddressOfRemoteServer();
        $path = sprintf('/session/%s/chromium/send_command', $this->webDriver->getSessionID());

        $client = new Client();
        $response = $client->post($url . $path, ['body' => json_encode($body)]);

        try {
            $responseData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $responseData = [];
        }

        return $responseData;
    }
}
