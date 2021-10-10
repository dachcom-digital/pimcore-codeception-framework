<?php

namespace Dachcom\Codeception\Helper\Browser;

use Codeception\Module;
use Codeception\Lib;
use Codeception\Exception\ModuleException;
use Dachcom\Codeception\Helper\PimcoreCore;
use Dachcom\Codeception\Helper\PimcoreUser;
use Dachcom\Codeception\Util\EditableHelper;
use Pimcore\Mail;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Document\Email;
use Pimcore\Model\User;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;
use Codeception\Module\Symfony;

class PhpBrowser extends Module implements Lib\Interfaces\DependsOnModule
{
    public const PIMCORE_ADMIN_CSRF_TOKEN_NAME = 'MOCK_CSRF_TOKEN';

    protected array $sessionSnapShot;
    protected PimcoreCore $pimcoreCore;

    public function _depends(): array
    {
        return [
            Symfony::class => 'PhpBrowser needs the pimcore core framework to work.'
        ];
    }

    public function _inject(PimcoreCore $pimcoreCore): void
    {
        $this->pimcoreCore = $pimcoreCore;
    }

    public function _initialize(): void
    {
        $this->sessionSnapShot = [];

        parent::_initialize();
    }

    /**
     * Actor Function to see a page with enabled edit-mode
     */
    public function amOnPageInEditMode(string $page): void
    {
        $this->pimcoreCore->amOnPage(sprintf('%s?pimcore_editmode=true', $page));
    }

    /**
     *  Actor Function to see a page with given locale
     */
    public function amOnPageWithLocale(string $url, ?string $locale): void
    {
        $parsedLocale = [];
        if (is_string($locale)) {
            $parsedLocale[] = $locale;
            if (str_contains($locale, '_')) {
                // add language ISO as fallback
                $parsedLocale[] = substr($locale, 0, 2);
            }
        } else {
            $parsedLocale = $locale;
        }

        $this->pimcoreCore->_loadPage('GET', $url, [], [], ['HTTP_ACCEPT_LANGUAGE' => implode(',', $parsedLocale)]);
    }

    /**
     *  Actor Function to see a page with given locale and country
     */
    public function amOnPageWithLocaleAndCountry(string $url, ?string $locale, string $country): void
    {
        $countryIps = [
            'hongKong'    => '21 59.148.0.0',
            'belgium'     => '31.5.255.255',
            'austria'     => '194.166.128.22',
            'germany'     => '2.175.255.255',
            'hungary'     => '188.142.192.35',
            'switzerland' => '5.148.191.255',
            'france'      => '46.162.191.255',
            'us'          => '52.33.249.128',
        ];

        if (!array_key_exists($country, $countryIps)) {
            throw new \Exception(sprintf('%s is not a valid test country', $country));
        }

        $parsedLocale = [];
        if (is_string($locale)) {
            $parsedLocale[] = $locale;
            if (str_contains($locale, '_')) {
                // add language ISO as fallback
                $parsedLocale[] = substr($locale, 0, 2);
            }
        } else {
            $parsedLocale = $locale;
        }

        $this->pimcoreCore->_loadPage('POST', $url, [], [], ['HTTP_ACCEPT_LANGUAGE' => implode(',', $parsedLocale), 'HTTP_CLIENT_IP' => $countryIps[$country]]);
    }

    /**
     * Actor Function to see if Link is a download file
     */
    public function seeDownloadLink(AbstractModel $element, string $link): void
    {
        $this->pimcoreCore->_loadPage('HEAD', $link);
        $response = $this->pimcoreCore->client->getInternalResponse();
        $headers = $response->getHeaders();

        $symfonyVersion = Kernel::MAJOR_VERSION;
        $contentDisposition = sprintf('attachment; filename=%s', ($symfonyVersion >= 4 ? $element->getKey() : sprintf('"%s"', $element->getKey())));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($contentDisposition, $headers['content-disposition'][0]);
        $this->assertEquals($element->getMimetype(), $headers['content-type'][0]);
    }

    /**
     * Actor Function to see if Link is a download file
     */
    public function seeDownloadLinkZip(string $fileName, string $link): void
    {
        $this->pimcoreCore->_loadPage('HEAD', $link);
        $response = $this->pimcoreCore->client->getInternalResponse();
        $headers = $response->getHeaders();

        $symfonyVersion = Kernel::MAJOR_VERSION;
        $contentDisposition = sprintf('attachment; filename=%s', ($symfonyVersion >= 4 ? $fileName : sprintf('"%s"', $fileName)));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($contentDisposition, $headers['content-disposition'][0]);
        $this->assertEquals('application/zip', $headers['content-type'][0]);
    }

    /**
     * Actor Function to see a page generated by a static route definition
     */
    public function amOnStaticRoute(string $routeName, array $args): void
    {
        $path = $this->pimcoreCore->getContainer()->get('router')->generate($routeName, $args, false);
        $this->pimcoreCore->amOnPage($path);
    }

    /**
     * Actor Function to see current uri matches given host
     */
    public function seeCurrentHostEquals(string $host): void
    {
        $server = $this->pimcoreCore->client->getHistory()->current()->getServer();
        $this->assertEquals($host, $server['HTTP_HOST']);
    }

    /**
     * Actor Function to see an editable on current page
     */
    public function seeAEditableConfiguration(string $name, string $type, ?string $label, array $options, $data = null, $selector = null): void
    {
        $this->pimcoreCore->see(EditableHelper::generateEditableConfiguration($name, $type, $label, $options, $data), $selector);
    }

    /**
     * Actor Function to see if given email has been with specified address
     * Only works with PhpBrowser (Symfony Client)
     */
    public function seeEmailIsSentTo(string $recipient, Email $email): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        $recipients = [];
        foreach ($collectedMessages as $message) {

            // yes. that's because it's impossible to fetch recipients in pimcore mail log while in debug mode.
            $htmlParser = new \DOMDocument();
            $htmlParser->loadHTML($message->getHtmlBody());
            $xpath = new \DOMXPath($htmlParser);

            $debugTable = $xpath->query('//table[@class="pimcore_debug_information"]')->item(0);

            if ($debugTable === null) {
                continue;
            }

            foreach ($debugTable->getElementsByTagName('tr') as $row) {

                if (str_contains($row->nodeValue, 'To:') === false) {
                    continue;
                }

                $recipients[] = str_replace('To: ', '', $row->nodeValue);
            }
        }

        $this->assertContains($recipient, $recipients);

    }

    /**
     * Actor Function to see if given email has been sent
     */
    public function seeSentEmailHasPropertyValue(Email $email, string $property, string $value): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        $getter = 'get' . ucfirst($property);
        foreach ($collectedMessages as $message) {
            $getterData = $message->$getter();
            if (is_array($getterData)) {
                $this->assertContains($value, array_keys($getterData));
            } else {
                $this->assertEquals($value, $getterData);
            }
        }
    }

    /**
     * Actor Function to see if given email has been submitted with specific charset of given type
     * Only works with PhpBrowser (Symfony Client)
     */
    public function seeEmailSubmissionType(string $submissionType, string $type, Email $email): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        foreach ($collectedMessages as $message) {
            $contentType = $type === 'html' ? $message->getHtmlCharset() : $message->getTextCharset();
            $this->assertEquals($submissionType, $contentType);
        }
    }

    /**
     * Actor Function to see if given email has no specific charset of given type
     * Only works with PhpBrowser (Symfony Client)
     */
    public function seeEmptyEmailSubmissionType(string $type, Email $email): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        foreach ($collectedMessages as $message) {
            $contentType = $type === 'html' ? $message->getHtmlCharset() : $message->getTextCharset();
            $this->assertEmpty($contentType);
        }
    }

    /**
     * Actor Function to see if given string is in real submitted mail body
     */
    public function seeInSubmittedEmailBody(string $string, Email $email): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        foreach ($collectedMessages as $message) {
            $this->assertContains($string, is_null($message->getBody()) ? '' : $message->getBody());
        }
    }

    /**
     * Actor Function to see if given string is not in real submitted mail body
     */
    public function dontSeeInSubmittedEmailBody(string $string, Email $email): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        foreach ($collectedMessages as $message) {
            $this->assertNotContains($string, is_null($message->getBody()) ? '' : $message->getBody());
        }
    }

    /**
     * Actor Function to see if given string is in mail body of type html or text
     */
    public function seeInSubmittedEmailBodyOfType(string $string, string $type, Email $email): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        foreach ($collectedMessages as $message) {
            $body = $type === 'html' ? $message->getHtmlBody() : $message->getTextBody();
            $this->assertStringContainsString($string, is_null($body) ? '' : $body);
        }
    }

    /**
     * Actor Function to see if given string is not in mail body of type html or text
     */
    public function dontSeeInSubmittedEmailBodyOfType(string $string, string $type, Email $email): void
    {
        $collectedMessages = $this->getCollectedEmails($email);

        foreach ($collectedMessages as $message) {
            $body = $type === 'html' ? $message->getHtmlBody() : $message->getTextBody();
            $this->assertStringNotContainsString($string, is_null($body) ? '' : $body);
        }
    }

    /**
     * Actor Function to log-in in front end
     */
    public function amLoggedInAsFrontendUser(?UserInterface $user, string $firewallName): void
    {
        if (!$user instanceof UserInterface) {
            $this->debug(sprintf('[PIMCORE BUNDLE MODULE] user needs to be a instance of %s.', UserInterface::class));
            return;
        }

        /** @var Session $session */
        $session = $this->pimcoreCore->getContainer()->get('session');

        $token = new UsernamePasswordToken($user, null, $firewallName, $user->getRoles());
        $this->pimcoreCore->getContainer()->get('security.token_storage')->setToken($token);

        $session->set('_security_' . $firewallName, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());

        $this->pimcoreCore->client->getCookieJar()->clear();
        $this->pimcoreCore->client->getCookieJar()->set($cookie);
    }

    /**
     * Actor Function to log-in into Pimcore Backend
     */
    public function amLoggedInAs(string $username): void
    {
        try {
            /** @var PimcoreUser $userModule */
            $userModule = $this->getModule('\\' . PimcoreUser::class);
        } catch (ModuleException $pimcoreModule) {
            $this->debug('[PIMCORE BUNDLE MODULE] could not load pimcore user module');
            return;
        }

        $pimcoreUser = $userModule->getUser($username);

        if (!$pimcoreUser instanceof User) {
            $this->debug(sprintf('[PIMCORE BUNDLE MODULE] could not fetch user %s.', $username));
            return;
        }

        \Pimcore\Tool\Session::useSession(function (AttributeBagInterface $adminSession) use ($pimcoreUser) {

            \Pimcore\Tool\Session::invalidate();

            $adminSession->set('user', $pimcoreUser);
            $adminSession->set('csrfToken', self::PIMCORE_ADMIN_CSRF_TOKEN_NAME);
        });

        $cookie = new Cookie(\Pimcore\Tool\Session::getSessionName(), \Pimcore\Tool\Session::getSessionId());

        $this->pimcoreCore->client->getCookieJar()->clear();
        $this->pimcoreCore->client->getCookieJar()->set($cookie);
    }

    /**
     * Actor Function to send tokenized ajax request in backend
     */
    public function sendTokenAjaxPostRequest(string $url, array $params = []): void
    {
        $params['csrfToken'] = self::PIMCORE_ADMIN_CSRF_TOKEN_NAME;
        $this->pimcoreCore->sendAjaxPostRequest($url, $params);
    }

    /**
     * Actor Function to see if last executed request is in given path
     */
    public function seeLastRequestIsInPath(string $expectedPath): void
    {
        $requestUri = $this->pimcoreCore->client->getInternalRequest()->getUri();
        $requestServer = $this->pimcoreCore->client->getInternalRequest()->getServer();

        $expectedUri = sprintf('http://%s%s', $requestServer['HTTP_HOST'], $expectedPath);

        $this->assertEquals($expectedUri, $requestUri);
    }

    /**
     * Actor Function to see canonical rel in link header
     */
    public function seeCanonicalLinkInResponse(): void
    {
        $link = $this->pimcoreCore->client->getInternalResponse()->getHeader('Link');

        $this->assertIsString($link);
        $this->assertContains('rel="canonical"', $link);
    }

    /**
     * Actor Function to not see canonical rel in link header
     */
    public function dontSeeCanonicalLinkInResponse(): void
    {
        $link = $this->pimcoreCore->client->getInternalResponse()->getHeader('Link');

        $this->assertNull($link);
    }

    /**
     * Actor Function to see pimcore output cached disabled header
     */
    public function seePimcoreOutputCacheDisabledHeader(string $disabledReasonMessage): void
    {
        $disabledReason = $this->pimcoreCore->client->getInternalResponse()->getHeader('X-Pimcore-Output-Cache-Disable-Reason');

        $this->assertEquals($disabledReasonMessage, $disabledReason);
    }

    /**
     * Actor Function to not see pimcore output cached disabled header
     */
    public function dontSeePimcoreOutputCacheDisabledHeader(): void
    {
        $disabledReason = $this->pimcoreCore->client->getInternalResponse()->getHeader('X-Pimcore-Output-Cache-Disable-Reason');

        $this->assertNull($disabledReason);
    }

    /**
     * Actor Function to not see pimcore output cached disabled header
     */
    public function seePimcoreOutputCacheDate(): void
    {
        $cacheDateHeader = $this->pimcoreCore->client->getInternalResponse()->getHeader('x-pimcore-cache-date');

        $this->assertNotNull($cacheDateHeader);
    }

    /**
     * Actor Function to assert empty session bag
     */
    public function seeEmptySessionBag(string $bagName): void
    {
        /** @var NamespacedAttributeBag $sessionBag */
        $sessionBag = $this->pimcoreCore->client->getRequest()->getSession()->getBag($bagName);

        $this->assertCount(0, $sessionBag->all());
    }

    /**
     * Actor Function to check if last _fragment request has given properties in request attributes
     */
    public function seePropertiesInLastFragmentRequest(array $properties = []): void
    {
        /** @var Profiler $profiler */
        $profiler = $this->pimcoreCore->_getContainer()->get('profiler');

        $tokens = $profiler->find('', '_fragment', 1, 'GET', '', '');
        if (count($tokens) === 0) {
            throw new \RuntimeException('No profile found. Is the profiler data collector enabled?');
        }

        $token = $tokens[0]['token'];
        /** @var Profile $profile */
        $profile = $profiler->loadProfile($token);

        if (!$profile instanceof Profile) {
            throw new \RuntimeException(sprintf('Profile with token "%s" not found.', $token));
        }

        /** @var RequestDataCollector $requestCollector */
        $requestCollector = $profile->getCollector('request');

        foreach ($properties as $property) {
            $this->assertTrue($requestCollector->getRequestAttributes()->has($property), sprintf('"%s" not found in request collector.', $property));
        }
    }

    /**
     * @return Mail[]
     */
    protected function getCollectedEmails(Email $email): array
    {
        $this->assertInstanceOf(Email::class, $email);

        /** @var Profiler $profiler */
        $profiler = $this->pimcoreCore->_getContainer()->get('profiler');

        $tokens = $profiler->find('', '', 1, 'POST', '', '');
        if (count($tokens) === 0) {
            throw new \RuntimeException('No profile found. Is the profiler data collector enabled?');
        }

        $token = $tokens[0]['token'];
        /** @var Profile $profile */
        $profile = $profiler->loadProfile($token);

        if (!$profile instanceof Profile) {
            throw new \RuntimeException(sprintf('Profile with token "%s" not found.', $token));
        }

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $profile->getCollector('mailer');

        $collectedMessages = $mailCollector->getEvents()->getMessages();

        $this->assertGreaterThan(0, count($collectedMessages));

        $emails = [];
        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            if (str_contains($message->getSubject(), sprintf('[%s]', $email->getProperty('test_identifier')))) {
                $emails[] = $message;
            }
        }

        return $emails;
    }
}
