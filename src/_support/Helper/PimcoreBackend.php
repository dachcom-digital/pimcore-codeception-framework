<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Util\Debug;
use Dachcom\Codeception\Util\EditableHelper;
use Dachcom\Codeception\Util\FileGeneratorHelper;
use Dachcom\Codeception\Util\SystemHelper;
use Dachcom\Codeception\Util\VersionHelper;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
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
     * @param string $locale
     *
     * @return Document\Snippet
     * @throws \Exception
     */
    public function haveASnippet($key = 'bundle-snippet-test', $params = [], $locale = 'en')
    {
        $document = $this->generateSnippet($key, $params, $locale);

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
     * @param array  $params
     * @param string $locale
     *
     * @return Document\Email
     */
    public function haveAEmail($key = 'bundle-email-test', array $params = [], $locale = 'en')
    {
        $document = $mailTemplate = $this->generateEmailDocument($key, $params, $locale);

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
     * @param array  $params
     *
     * @return DataObject\Concrete
     * @throws \Exception
     */
    public function haveAPimcoreObject(string $objectType, $key = 'bundle-object-test', array $params = [])
    {
        $object = $this->generateObject($objectType, $key, $params);

        try {
            $object->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating object. message was: ' . $e->getMessage()));
            return null;
        }

        $this->assertInstanceOf(get_class($object), DataObject::getById($object->getId()));

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

        $this->assertInstanceOf(Asset::class, Asset::getById($asset->getId()));

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
     * @param Document $document
     * @param array    $editables
     *
     * @throws \Exception
     */
    public function seeEditablesPlacedOnDocument(Document $document, array $editables)
    {
        if (!$document instanceof Document\Snippet || !$document instanceof Document\Page) {
            throw new ModuleException($this, sprintf('%s must be instance of %s or %s.', $document->getFullPath(), Document\Snippet::class, Document\Page::class));
        }

        try {
            $editables = EditableHelper::generateEditables($editables);
        } catch (\Throwable $e) {
            throw new ModuleException($this, sprintf('editable generator error: %s', $e->getMessage()));
        }

        if (VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0')) {
            $document->setEditables($editables);
        } else {
            $document->setElements($editables);
        }

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while adding editables to document. message was: ' . $e->getMessage()));
        }

        $this->assertCount(count($editables), VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0') ? $document->getEditables() : $document->getElements());
    }

    /**
     * Actor Function to place a area on a document
     *
     * @param Document $document
     * @param string   $areaName
     * @param array    $editables
     *
     * @throws ModuleException
     * @deprecated
     */
    public function seeAnAreaElementPlacedOnDocument(Document $document, string $areaName, array $editables = [])
    {
        if (!$document instanceof Document\Snippet && !$document instanceof Document\Page) {
            throw new ModuleException($this, sprintf('%s must be instance of %s or %s.', $document->getFullPath(), Document\Snippet::class, Document\Page::class));
        }

         try {
            $editables = EditableHelper::generateEditablesForArea($areaName, $editables);
        } catch (\Throwable $e) {
            throw new ModuleException($this, sprintf('area generator error: %s', $e->getMessage()));
        }

        if (VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0')) {
            $document->setEditables($editables);
        } else {
            $document->setElements($editables);
        }

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while adding area element to document. message was: ' . $e->getMessage()));
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
     * @return DataObject\ClassDefinition
     * @throws ModuleException
     */
    public function haveAPimcoreClass(string $name = 'TestClass')
    {
        $cm = $this->getClassManager();

        $bundleClass = getenv('TEST_BUNDLE_TEST_DIR');
        $path = sprintf('%s/_etc/classes', $bundleClass);

        $class = $cm->setupClass($name, sprintf('%s/%s.json', $path, $name));
        $this->assertInstanceOf(DataObject\ClassDefinition::class, $class);

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
    protected function generatePageDocument($key = 'test-page', $params = [], $locale = 'en')
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

        $this->assignMethods($document, $params);

        return $document;
    }

    /**
     * API Function to create a Snippet
     *
     * @param string $key
     * @param array  $params
     * @param string $locale
     *
     * @return null|Document\Snippet
     * @throws \Exception
     */
    protected function generateSnippet($key = 'test-snippet', $params = [], $locale = 'en')
    {
        $document = new Document\Snippet();

        $document->setType('snippet');
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setPublished(true);

        $document->setKey($key);
        $document->setProperty('language', 'text', $locale, false, 1);

        $this->assignMethods($document, $params);

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
        $document->setPublished(true);

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

        $this->assignMethods($document, $params);

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

        $this->assignMethods($asset, $params);

        return $asset;
    }

    /**
     * API Function to create a object
     *
     * @param string $objectType
     * @param string $key
     * @param array  $params
     *
     * @return DataObject\Concrete
     */
    protected function generateObject(string $objectType, $key = 'test-object', array $params = [])
    {
        $type = sprintf('\\Pimcore\\Model\\DataObject\\%s', $objectType);
        $object = TestHelper::createEmptyObject($key, true, false, $type);

        $object->setKey($key);

        if (isset($params['properties'])) {
            $object->setProperties($params['properties']);
            unset($params['properties']);
        }

        $this->assignMethods($object, $params);

        return $object;
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

    /**
     * @param ElementInterface $entity
     * @param array            $params
     */
    protected function assignMethods($entity, array $params)
    {
        if (count($params) === 0) {
            return;
        }

        foreach ($params as $varKey => $varValue) {
            $setter = sprintf('set%s', ucfirst($varKey));

            $this->assertTrue(method_exists($entity, $setter), sprintf('method %s does not exist in entity %s', $setter, get_class($entity)));

            $entity->$setter($varValue);
        }
    }
}
