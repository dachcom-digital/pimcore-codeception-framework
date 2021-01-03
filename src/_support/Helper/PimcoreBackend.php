<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Util\Debug;
use Dachcom\Codeception\Util\FileGeneratorHelper;
use Dachcom\Codeception\Util\SystemHelper;
use Dachcom\Codeception\Util\VersionHelper;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Tests\Helper\ClassManager;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Model\Document;
use Pimcore\Translation\Translator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Serializer\Serializer;

class PimcoreBackend extends Module
{
    /**
     * @param TestInterface $test
     */
    public function _before(TestInterface $test)
    {
        FileGeneratorHelper::preparePaths();

        parent::_before($test);
    }

    /**
     * @param TestInterface $test
     */
    public function _after(TestInterface $test)
    {
        SystemHelper::cleanUp();
        FileGeneratorHelper::cleanUp();

        parent::_after($test);
    }

    /**
     * Actor Function to create a Page Document
     *
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Page
     * @throws \Exception
     */
    public function haveAPageDocument($key = 'bundle-page-test', $params = [], $locale = 'en')
    {
        $document = $this->generatePageDocument($key, $params, $locale);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving document page. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Page::class, Document\Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Snippet
     *
     * @param string $key
     * @param array  $params
     * @param array  $elements
     *
     * @return Document\Snippet
     * @throws \Exception
     *
     */
    public function haveASnippet($key = 'bundle-snippet-test', $params = [], $elements = [])
    {
        $document = $this->generateSnippet($key, $params, $elements);

        try {
            $document->save();
        } catch (\Exception $e) {
            $this->debug(sprintf('[TEST BUNDLE ERROR]: error while saving snippet: ' . $e->getMessage()));
            return null;
        }

        $this->assertInstanceOf(Document\Snippet::class, Document\Snippet::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a mail document
     *
     * @param string $key
     * @param array  $mailParams
     * @param string $locale
     *
     * @return Document\Email
     */
    public function haveAEmail($key = 'bundle-email-test', array $mailParams = [], $locale = 'en')
    {
        $document = $mailTemplate = $this->generateEmailDocument($key, $mailParams, $locale);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating email. message was: ' . $e->getMessage()));
            return null;
        }

        $this->assertInstanceOf(Document\Email::class, Document\Email::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a pimcore object
     *
     * @param string $objectType
     * @param string $key
     * @param null   $parent
     *
     * @return Concrete
     * @throws \Exception
     */
    public function haveAPimcoreObject(string $objectType, $key = 'bundle-object-test', $parent = null)
    {
        $type = sprintf('\\Pimcore\\Model\\DataObject\\%s', $objectType);
        $object = TestHelper::createEmptyObject($key, true, false, $type);

        if ($parent !== null) {
            $object->setParent($parent);
        }

        try {
            $object->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating object. message was: ' . $e->getMessage()));
            return null;
        }

        $this->assertInstanceOf($type, $object);

        return $object;
    }

    /**
     * Actor Function to create a pimcore asset
     *
     * @param string $key
     * @param array  $params
     *
     * @return Asset
     * @throws \Exception
     */
    public function haveAPimcoreAsset($key = 'bundle-asset-test', array $params = [])
    {
        $asset = $this->generateAsset($key, $params);

        try {
            $asset->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating asset. message was: ' . $e->getMessage()));
            return null;
        }

        $this->assertInstanceOf(Asset::class, $asset);

        return $asset;
    }

    /**
     * Actor function to generate a dummy asset file.
     *
     * @param string $fileName
     * @param int    $fileSizeInMb Mb
     */
    public function haveADummyFile($fileName, $fileSizeInMb = 1)
    {
        FileGeneratorHelper::generateDummyFile($fileName, $fileSizeInMb);
    }

    /**
     * Actor function to see a generated dummy file in download directory.
     *
     * @param $fileName
     */
    public function seeDownload($fileName)
    {
        $supportDir = FileGeneratorHelper::getDownloadPath();
        $filePath = $supportDir . $fileName;

        $this->assertTrue(is_file($filePath));
    }

    /**
     * Actor Function to place a area on a document
     *
     * @param Document\Page $document
     * @param array         $editables
     */
    public function seeAnAreaElementPlacedOnDocument(Document\Page $document, array $editables = [])
    {
        if (VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0')) {
            $document->setEditables($editables);
        } else {
            $document->setElements($editables);
        }

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving document. message was: ' . $e->getMessage()));
        }

        $this->assertCount(count($editables), VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0') ? $document->getEditables() : $document->getElements());
    }

    /**
     * Actor Function to see if given email has been sent
     *
     * @param Document\Email $email
     */
    public function seeEmailIsSent(Document\Email $email)
    {
        $this->assertInstanceOf(Document\Email::class, $email);

        $foundEmails = $this->getEmailsFromDocumentIds([$email->getId()]);
        $this->assertEquals(1, count($foundEmails));
    }

    /**
     * Actor Function to see if an email has been sent to admin
     *
     * @param Document\Email $email
     */
    public function seeEmailIsNotSent(Document\Email $email)
    {
        $this->assertInstanceOf(Document\Email::class, $email);

        $foundEmails = $this->getEmailsFromDocumentIds([$email->getId()]);
        $this->assertEquals(0, count($foundEmails));
    }

    /**
     * Actor Function to see if admin email contains given properties
     *
     * @param Document\Email $mail
     * @param array          $properties
     */
    public function seePropertiesInEmail(Document\Email $mail, array $properties)
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        $serializer = $this->getSerializer();

        foreach ($foundEmails as $email) {
            $params = $serializer->decode($email->getParams(), 'json', ['json_decode_associative' => true]);
            foreach ($properties as $propertyKey => $propertyValue) {
                $key = array_search($propertyKey, array_column($params, 'key'));
                if ($key === false) {
                    $this->fail(sprintf('Failed asserting that mail params array has the key "%s".', $propertyKey));
                }

                $data = $params[$key];
                $this->assertEquals($propertyValue, $data['data']['value']);
            }
        }
    }

    /**
     * Actor Function to see if admin email contains given properties
     *
     * @param Document\Email $mail
     * @param array          $properties
     */
    public function seePropertyKeysInEmail(Document\Email $mail, array $properties)
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        $serializer = $this->getSerializer();

        foreach ($foundEmails as $email) {
            $params = $serializer->decode($email->getParams(), 'json', ['json_decode_associative' => true]);
            foreach ($properties as $propertyKey) {
                $key = array_search($propertyKey, array_column($params, 'key'));
                $this->assertNotSame(false, $key);
            }
        }
    }

    /**
     * Actor Function to see if admin email not contains given properties
     *
     * @param Document\Email $mail
     * @param array          $properties
     */
    public function cantSeePropertyKeysInEmail(Document\Email $mail, array $properties)
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        $serializer = $this->getSerializer();

        foreach ($foundEmails as $email) {
            $params = $serializer->decode($email->getParams(), 'json', ['json_decode_associative' => true]);
            foreach ($properties as $propertyKey) {
                $this->assertFalse(
                    array_search(
                        $propertyKey,
                        array_column($params, 'key')),
                    sprintf('Failed asserting that search for "%s" is false.', $propertyKey)
                );
            }
        }
    }

    /**
     * Actor Function to see rendered body text in given email
     *
     * @param Document\Email $mail
     * @param string         $string
     */
    public function seeInRenderedEmailBody(Document\Email $mail, string $string)
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        $serializer = $this->getSerializer();

        foreach ($foundEmails as $email) {
            $params = $serializer->decode($email->getParams(), 'json', ['json_decode_associative' => true]);

            $bodyKey = array_search('body', array_column($params, 'key'));
            $this->assertNotSame(false, $bodyKey);

            $data = $params[$bodyKey];
            $this->assertContains($string, $data['data']['value']);
        }
    }

    /**
     * Actor Function to see if a key has been stored in admin translations
     *
     * @param string $key
     */
    public function seeKeyInFrontendTranslations(string $key)
    {
        /** @var Translator $translator */
        $translator = \Pimcore::getContainer()->get('pimcore.translator');
        $this->assertTrue($translator->getCatalogue()->has($key));
    }

    /**
     * Actor Function to generate a single static route.
     *
     * @param string $name
     * @param array  $params
     *
     * @return Staticroute
     */
    public function haveAStaticRoute(string $name = 'test_route', array $params = [])
    {
        $defaults = [
            'id'               => 1,
            'name'             => $name,
            'module'           => 'AppBundle',
            'controller'       => '@AppBundle\\Controller\\DefaultController',
            'defaults'         => null,
            'siteId'           => [],
            'priority'         => 0,
            'legacy'           => false,
            'creationDate'     => 1545383519,
            'modificationDate' => 1545383619
        ];

        $data = array_merge($defaults, $params);

        $route = new Staticroute();
        $route->setValues($data);
        $route->save();

        $this->assertInstanceOf(Staticroute::class, $route);

        return $route;
    }

    /**
     * Actor Function to generate a pimcore class from json definition file.
     *
     * @param string $name
     *
     * @return ClassDefinition
     * @throws ModuleException
     */
    public function haveAPimcoreClass(string $name = 'TestClass')
    {
        $cm = $this->getClassManager();

        $bundleClass = getenv('TEST_BUNDLE_TEST_DIR');
        $path = sprintf('%s/_etc/classes', $bundleClass);

        $class = $cm->setupClass($name, sprintf('%s/%s.json', $path, $name));
        $this->assertInstanceOf(ClassDefinition::class, $class);

        return $class;
    }

    /**
     * API Function to get sent email ids from given document ids
     *
     * @public to allow usage from other modules
     *
     * @param array $documentIds
     *
     * @return Log[]
     */
    public function getEmailsFromDocumentIds(array $documentIds)
    {
        $emailLogs = new Log\Listing();
        $emailLogs->addConditionParam(sprintf('documentId IN (%s)', implode(',', $documentIds)));

        return $emailLogs->load();
    }

    /**
     * API Function to get pimcore serializer
     *
     * @public to allow usage from other modules
     * @return Serializer
     *
     */
    public function getSerializer()
    {
        $serializer = null;

        try {
            $serializer = $this->getContainer()->get('pimcore_admin.serializer');
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while getting pimcore admin serializer. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Serializer::class, $serializer);

        return $serializer;
    }

    /**
     * API Function to create a page document
     *
     * @param string $key
     * @param array  $params
     * @param string $locale
     *
     * @return Document\Page
     * @throws \Exception
     */
    protected function generatePageDocument($key = 'bundle-test', $params = [], $locale = 'en')
    {
        if (!isset($params['action'])) {
            $params['action'] = 'default';
        }

        if (!isset($params['controller'])) {
            $params['controller'] = '@AppBundle\Controller\DefaultController';
        }

        $document = TestHelper::createEmptyDocumentPage('', false);

        $document->setKey($key);
        $document->setProperty('language', 'text', $locale, false, 1);

        if (count($params) > 0) {
            foreach ($params as $varKey => $varValue) {
                $document->setObjectVar($varKey, $varValue);
            }
        }

        return $document;
    }

    /**
     * API Function to create a Snippet
     *
     * @param string $key
     * @param array  $params
     * @param array  $editables
     * @param string $locale
     *
     * @return null|Document\Snippet
     * @throws \Exception
     */
    protected function generateSnippet($key, $params = [], $editables = [], $locale = 'en')
    {
        $document = new Document\Snippet();

        $document->setType('snippet');
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setKey($key);
        $document->setPublished(true);
        $document->setProperty('language', 'text', $locale, false, 1);

        if (count($params) > 0) {
            foreach ($params as $varKey => $varValue) {
                $document->setObjectVar($varKey, $varValue);
            }
        }

        if (count($editables) > 0) {
            if (VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0')) {
                $document->setEditables($editables);
            } else {
                $document->setElements($editables);
            }
        }

        return $document;
    }

    /**
     * API Function to create a email document
     *
     * @param string $key
     * @param array  $params
     * @param string $locale
     *
     * @return Document\Email
     */
    protected function generateEmailDocument($key = 'test-email', array $params = [], $locale = 'en')
    {
        $documentKey = uniqid(sprintf('%s-', $key));

        $document = new Document\Email();
        $document->setType('email');
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());

        $document->setKey($documentKey);
        $document->setProperty('language', 'text', $locale, false, 1);

        if (!isset($params['to'])) {
            $params['to'] = 'recpient@test.org';
        }

        if (!isset($params['subject'])) {
            $params['subject'] = sprintf('TEST BUNDLE EMAIL %s', $documentKey);
        }

        if (isset($params['properties'])) {
            $document->setProperties($params['properties']);
            unset($params['properties']);
        }

        if (count($params) > 0) {
            foreach ($params as $varKey => $varValue) {
                $document->setObjectVar($varKey, $varValue);
            }
        }

        return $document;
    }

    /**
     * API Function to create a asset element
     *
     * @param string $key
     * @param array  $params
     *
     * @return Asset
     */
    protected function generateAsset($key = 'test-asset', array $params = [])
    {
        $asset = TestHelper::createImageAsset($key, false, false);
        $asset->setKey($key);

        if (isset($params['properties'])) {
            $asset->setProperties($params['properties']);
            unset($params['properties']);
        }

        if (count($params) > 0) {
            foreach ($params as $varKey => $varValue) {
                $asset->setObjectVar($varKey, $varValue);
            }
        }

        return $asset;
    }

    /**
     * @return Container
     * @throws ModuleException
     */
    protected function getContainer()
    {
        return $this->getModule('\\' . PimcoreCore::class)->getContainer();
    }

    /**
     * @return Module|ClassManager
     * @throws ModuleException
     */
    protected function getClassManager()
    {
        return $this->getModule('\\' . ClassManager::class);
    }
}
