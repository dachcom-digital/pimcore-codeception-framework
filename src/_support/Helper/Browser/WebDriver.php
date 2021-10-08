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
     */
    public function amOnPageInEditMode(string $page): void
    {
        $this->amOnPage(sprintf('%s?pimcore_editmode=true', $page));
    }

    /**
     * Actor Function to declare web driver download behaviour
     */
    public function setDownloadPathForWebDriver($path = null): void
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
    public function clearWebDriverCache(): void
    {
        $body = [
            'cmd'    => 'Network.clearBrowserCache',
            'params' => ['params' => []]
        ];

        $responseData = $this->sendWebDriverCommand($body);

        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals(0, $responseData['status']);
    }

    public function seeAEditableConfiguration(string $name, string $type, array $options, $data = null, $selector = null): void
    {
        $this->see(EditableHelper::generateEditableConfiguration($name, $type, $options, $data), $selector);
    }

    protected function sendWebDriverCommand(array $body): array
    {
        $url = $this->webDriver->getCommandExecutor()->getAddressOfRemoteServer();
        $path = sprintf('/session/%s/chromium/send_command', $this->webDriver->getSessionID());

        $client = new Client();
        $response = $client->post($url . $path, ['body' => json_encode($body)]);

        try {
            $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $responseData = [];
        }

        return $responseData;
    }
}
