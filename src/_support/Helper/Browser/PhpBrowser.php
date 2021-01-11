<?php

namespace Dachcom\Codeception\Helper\Browser;

use Codeception\Module;
use Codeception\Lib;
use Codeception\Exception\ModuleException;
use Dachcom\Codeception\Helper\PimcoreCore;
use Dachcom\Codeception\Helper\PimcoreUser;
use Dachcom\Codeception\Util\EditableHelper;
use Dachcom\Codeception\Util\VersionHelper;
use Pimcore\Mail;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Document\Email;
use Pimcore\Model\User;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;

class PhpBrowser extends Module implements Lib\Interfaces\DependsOnModule
{
    const PIMCORE_ADMIN_CSRF_TOKEN_NAME = 'MOCK_CSRF_TOKEN';

    /**
     * @var Cookie
     */
    protected $sessionSnapShot;

    /**
     * @var PimcoreCore
     */
    protected $pimcoreCore;

    /**
     * @return array|mixed
     */
    public function _depends()
    {
        return [
            'Codeception\Module\Symfony' => 'PhpBrowser needs the pimcore core framework to work.'
        ];
    }

    /**
     * @param PimcoreCore $pimcoreCore
     */
    public function _inject($pimcoreCore)
    {
        $this->pimcoreCore = $pimcoreCore;
    }

    /**
     * @inheritDoc
     */
    public function _initialize()
    {
        $this->sessionSnapShot = [];

        parent::_initialize();
    }

    /**
     * Actor Function to see a page with enabled edit-mode
     *
     * @param string $page
     */
    public function amOnPageInEditMode(string $page)
    {
        $this->pimcoreCore->amOnPage(sprintf('%s?pimcore_editmode=true', $page));
    }

    /**
     *  Actor Function to see a page with given locale
     *
     * @param string       $url
     * @param string|array $locale
     */
    public function amOnPageWithLocale($url, $locale)
    {
        $parsedLocale = [];
        if (is_string($locale)) {
            $parsedLocale[] = $locale;
            if (strpos($locale, '_') !== false) {
                // add language ISO as fallback
                $parsedLocale[] = substr($locale, 0, 2);
            }
        } else {
            $parsedLocale = $locale;
        }

        $this->pimcoreCore->_loadPage('GET', $url, [], [], ['HTTP_ACCEPT_LANGUAGE' => join(',', $parsedLocale)]);
    }

    /**
     *  Actor Function to see a page with given locale and country
     *
     * @param string       $url
     * @param string|array $locale
     * @param string       $country
     *
     * @throws \Exception
     */
    public function amOnPageWithLocaleAndCountry($url, $locale, $country)
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

        if (!key_exists($country, $countryIps)) {
            throw new \Exception(sprintf('%s is not a valid test country', $country));
        }

        $parsedLocale = [];
        if (is_string($locale)) {
            $parsedLocale[] = $locale;
            if (strpos($locale, '_') !== false) {
                // add language ISO as fallback
                $parsedLocale[] = substr($locale, 0, 2);
            }
        } else {
            $parsedLocale = $locale;
        }

        $this->pimcoreCore->_loadPage('POST', $url, [], [], ['HTTP_ACCEPT_LANGUAGE' => join(',', $parsedLocale), 'HTTP_CLIENT_IP' => $countryIps[$country]]);
    }

    /**
     * Actor Function to see if Link is a download file
     *
     * @param AbstractModel $element
     * @param string        $link
     */
    public function seeDownloadLink(AbstractModel $element, string $link)
    {
        $this->pimcoreCore->_loadPage('HEAD', $link);
        $response = $this->pimcoreCore->client->getInternalResponse();
        $headers = $response->getHeaders();

        $symfonyVersion = Kernel::MAJOR_VERSION;
        $contentDisposition = sprintf('attachment; filename=%s', ($symfonyVersion >= 4 ? $element->getKey() : sprintf('"%s"', $element->getKey())));

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals($contentDisposition, $headers['content-disposition'][0]);
        $this->assertEquals($element->getMimetype(), $headers['content-type'][0]);
    }

    /**
     * Actor Function to see if Link is a download file
     *
     * @param string $fileName
     * @param string $link
     */
    public function seeDownloadLinkZip(string $fileName, string $link)
    {
        $this->pimcoreCore->_loadPage('HEAD', $link);
        $response = $this->pimcoreCore->client->getInternalResponse();
        $headers = $response->getHeaders();

        $symfonyVersion = Kernel::MAJOR_VERSION;
        $contentDisposition = sprintf('attachment; filename=%s', ($symfonyVersion >= 4 ? $fileName : sprintf('"%s"', $fileName)));

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals($contentDisposition, $headers['content-disposition'][0]);
        $this->assertEquals('application/zip', $headers['content-type'][0]);
    }

    /**
     * Actor Function to see a page generated by a static route definition.
     *
     * @param string $routeName
     * @param array  $args
     */
    public function amOnStaticRoute(string $routeName, array $args)
    {
        $path = $this->pimcoreCore->getContainer()->get('router')->generate($routeName, $args, false);
        $this->pimcoreCore->amOnPage($path);
    }

    /**
     * Actor Function to see current uri matches given host
     *
     * @param $host
     */
    public function seeCurrentHostEquals($host)
    {
        $server = $this->pimcoreCore->client->getHistory()->current()->getServer();
        $this->assertEquals($host, $server['HTTP_HOST']);
    }

    /**
     * Actor Function to see a editable on current page.
     *
     * @param string $name
     * @param string $type
     * @param array  $options
     * @param null   $data
     * @param null   $selector
     */
    public function seeAEditableConfiguration(string $name, string $type, array $options, $data = null, $selector = null)
    {
        $this->pimcoreCore->see(EditableHelper::generateEditableConfiguration($name, $type, $options, $data), $selector);
    }

    /**
     * Actor Function to see if given email has been with specified address
     * Only works with PhpBrowser (Symfony Client)
     *
     * @param string $recipient
     * @param Email  $email
     */
    public function seeEmailIsSentTo(string $recipient, Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        $recipients = [];
        foreach ($collectedMessages as $message) {
            if ($email->getSubject() !== $message->getSubject()) {
                continue;
            }
            $recipients = array_merge($recipients, $message->getTo());
        }

        $this->assertContains($recipient, array_keys($recipients));

    }

    /**
     * Actor Function to see if given email has been sent
     *
     * @param Email  $email
     * @param string $property
     * @param string $value
     */
    public function seeSentEmailHasPropertyValue(Email $email, string $property, string $value)
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
     * Actor Function to see if given email has been with specified address
     * Only works with PhpBrowser (Symfony Client)
     *
     * @param string $submissionType
     * @param Email  $email
     *
     * @throws \ReflectionException
     */
    public function seeEmailSubmissionType(string $submissionType, Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            if (method_exists($message, 'getBodyContentType')) {
                $contentType = $message->getBodyContentType();
            } else {
                // swift mailer < 6.0
                $reflectionClass = new \ReflectionClass($message);
                $contentTypeProperty = $reflectionClass->getProperty('_userContentType');
                $contentTypeProperty->setAccessible(true);
                $contentType = $contentTypeProperty->getValue($message);
            }
            $this->assertEquals($submissionType, $contentType);
        }
    }

    /**
     * Actor Function to see if given string is in real submitted mail body
     *
     * @param string $string
     * @param Email  $email
     */
    public function seeInSubmittedEmailBody(string $string, Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            $this->assertContains($string, is_null($message->getBody()) ? '' : $message->getBody());
        }
    }

    /**
     * Actor Function to see if given string is in real submitted mail body
     *
     * @param string $string
     * @param Email  $email
     */
    public function dontSeeInSubmittedEmailBody(string $string, Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            $this->assertNotContains($string, is_null($message->getBody()) ? '' : $message->getBody());
        }
    }

    /**
     * Actor Function to see if message has children
     *
     * @param Email $email
     */
    public function haveSubmittedEmailChildren(Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            $this->assertGreaterThan(0, count($message->getChildren()));
        }
    }

    /**
     * Actor Function to see if message has no children
     *
     * @param Email $email
     */
    public function dontHaveSubmittedEmailChildren(Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            $this->assertEquals(0, count($message->getChildren()));
        }
    }

    /**
     * Actor Function to see if given string is in real submitted child body
     *
     * @param string $string
     * @param Email  $email
     */
    public function seeInSubmittedEmailChildrenBody(string $string, Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        /** @var Mail $message */
        foreach ($collectedMessages as $message) {

            $this->assertGreaterThan(0, count($message->getChildren()));

            /** @var \Swift_Mime_SimpleMimeEntity $child */
            foreach ($message->getChildren() as $child) {
                $this->assertContains($string, is_null($child->getBody()) ? '' : $child->getBody());
            }
        }
    }

    /**
     * Actor Function to see if given string is not in real submitted child body
     *
     * @param string $string
     * @param Email  $email
     */
    public function dontSeeInSubmittedEmailChildrenBody(string $string, Email $email)
    {
        $collectedMessages = $this->getCollectedEmails($email);

        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            /** @var \Swift_Mime_SimpleMimeEntity $child */
            foreach ($message->getChildren() as $child) {
                $this->assertNotContains($string, is_null($child->getBody()) ? '' : $child->getBody());
            }
        }
    }

    /**
     * Actor Function to login in FrontEnd
     *
     * @param UserInterface $user
     * @param string        $firewallName
     */
    public function amLoggedInAsFrontendUser(UserInterface $user, string $firewallName)
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
     * Actor Function to login into Pimcore Backend
     *
     * @param $username
     */
    public function amLoggedInAs($username)
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

        \Pimcore\Tool\Session::invalidate();
        \Pimcore\Tool\Session::useSession(function (AttributeBagInterface $adminSession) use ($pimcoreUser) {
            $adminSession->set('user', $pimcoreUser);
        });

        $cookie = new Cookie(\Pimcore\Tool\Session::getSessionName(), \Pimcore\Tool\Session::getSessionId());

        $this->pimcoreCore->client->getCookieJar()->clear();
        $this->pimcoreCore->client->getCookieJar()->set($cookie);

    }

    /**
     * Actor Function to send tokenized ajax request in backend
     *
     * @param string $url
     * @param array  $params
     */
    public function sendTokenAjaxPostRequest(string $url, array $params = [])
    {
        $params['csrfToken'] = self::PIMCORE_ADMIN_CSRF_TOKEN_NAME;
        $this->pimcoreCore->sendAjaxPostRequest($url, $params);
    }

    /**
     * Actor Function to see if last executed request is in given path
     *
     * @param string $expectedPath
     */
    public function seeLastRequestIsInPath(string $expectedPath)
    {
        $requestUri = $this->pimcoreCore->client->getInternalRequest()->getUri();
        $requestServer = $this->pimcoreCore->client->getInternalRequest()->getServer();

        $expectedUri = sprintf('http://%s%s', $requestServer['HTTP_HOST'], $expectedPath);

        $this->assertEquals($expectedUri, $requestUri);
    }

    /**
     * Actor Function to see canonical rel in link header
     */
    public function seeCanonicalLinkInResponse()
    {
        $link = $this->pimcoreCore->client->getInternalResponse()->getHeader('Link');

        $this->assertInternalType('string', $link);
        $this->assertContains('rel="canonical"', $link);
    }

    /**
     * Actor Function to not see canonical rel in link header
     */
    public function dontSeeCanonicalLinkInResponse()
    {
        $link = $this->pimcoreCore->client->getInternalResponse()->getHeader('Link');

        $this->assertNull($link);
    }

    /**
     * Actor Function to see pimcore output cached disabled header
     *
     * @param $disabledReasonMessage
     */
    public function seePimcoreOutputCacheDisabledHeader($disabledReasonMessage)
    {
        $disabledReason = $this->pimcoreCore->client->getInternalResponse()->getHeader('X-Pimcore-Output-Cache-Disable-Reason');

        $this->assertEquals($disabledReasonMessage, $disabledReason);
    }

    /**
     * Actor Function to not to see pimcore output cached disabled header
     */
    public function dontSeePimcoreOutputCacheDisabledHeader()
    {
        $disabledReason = $this->pimcoreCore->client->getInternalResponse()->getHeader('X-Pimcore-Output-Cache-Disable-Reason');

        $this->assertNull($disabledReason);
    }

    /**
     * Actor Function to not to see pimcore output cached disabled header
     */
    public function seePimcoreOutputCacheDate()
    {
        $cacheDateHeader = $this->pimcoreCore->client->getInternalResponse()->getHeader('x-pimcore-cache-date');

        $this->assertNotNull($cacheDateHeader);
    }

    /**
     * Actor Function to assert empty session bag
     *
     * @param string $bagName
     */
    public function seeEmptySessionBag(string $bagName)
    {
        /** @var NamespacedAttributeBag $sessionBag */
        $sessionBag = $this->pimcoreCore->client->getRequest()->getSession()->getBag($bagName);

        $this->assertCount(0, $sessionBag->all());
    }

    /**
     * Actor Function to check if last _fragment request has given properties in request attributes.
     *
     * @param array $properties
     */
    public function seePropertiesInLastFragmentRequest(array $properties = [])
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
     * @param Email $email
     *
     * @return array
     */
    protected function getCollectedEmails(Email $email)
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
        $mailCollector = $profile->getCollector('swiftmailer');

        $this->assertGreaterThan(0, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();

        $emails = [];
        /** @var Mail $message */
        foreach ($collectedMessages as $message) {
            if ($email->getProperty('test_identifier') !== $message->getDocument()->getProperty('test_identifier')) {
                continue;
            }
            $emails[] = $message;
        }

        return $emails;
    }
}
