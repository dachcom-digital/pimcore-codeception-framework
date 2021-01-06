<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Util\Debug;
use Dachcom\Codeception\Helper\Browser\PhpBrowser;
use Dachcom\Codeception\Util\EditableHelper;
use Dachcom\Codeception\Util\FileGeneratorHelper;
use Dachcom\Codeception\Util\SystemHelper;
use Dachcom\Codeception\Util\VersionHelper;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Redirect;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Model\Translation\Website;
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
    public function haveAPageDocument($key = 'bundle-page-test', array $params = [], $locale = null)
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
     * Actor Function to create a Child Page Document
     *
     * @param Document    $parent
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Page
     * @throws \Exception
     */
    public function haveASubPageDocument(Document $parent, $key = 'bundle-sub-page-test', array $params = [], $locale = null)
    {
        $document = $this->generatePageDocument($key, $params, $locale);
        $document->setParentId($parent->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving child document page. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Page::class, Document\Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Snippet
     *
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Snippet
     * @throws \Exception
     */
    public function haveASnippet($key = 'bundle-snippet-test', $params = [], $locale = null)
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
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Email
     */
    public function haveAEmail($key = 'bundle-email-test', array $params = [], $locale = null)
    {
        $document = $this->generateEmailDocument($key, $params, $locale);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating email. message was: ' . $e->getMessage()));
            return null;
        }

        // needed?
        //\Pimcore\Cache\Runtime::set(sprintf('document_%s', $document->getId()), null);

        $this->assertInstanceOf(Document\Email::class, Document\Email::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a link
     *
     * @param Document\Page $source
     * @param string        $key
     * @param array         $params
     * @param string        $locale
     *
     * @return Document\Link
     */
    public function haveALink(Document\Page $source, $key = 'bundle-link-test', array $params = [], $locale = null)
    {
        $link = $this->generateLink($source, $key, $params, $locale);

        try {
            $link->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving link. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Link::class, Document\Link::getById($link->getId()));

        return $link;
    }

    /**
     * Actor Function to create a link
     *
     * @param Document      $parent
     * @param Document\Page $source
     * @param string        $key
     * @param array         $params
     * @param string        $locale
     *
     * @return Document\Link
     */
    public function haveASubLink(Document $parent, Document\Page $source, $key = 'bundle-sub-link-test', array $params = [], $locale = null)
    {
        $link = $this->generateLink($source, $key, $params, $locale);
        $link->setParent($parent);

        try {
            $link->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving sub link. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Link::class, Document\Link::getById($link->getId()));

        return $link;
    }

    /**
     * Actor Function to create a Hardlink
     *
     * @param Document\Page $source
     * @param string        $key
     * @param array         $params
     * @param string        $locale
     *
     * @return Document\Hardlink
     */
    public function haveAHardLink(Document\Page $source, $key = 'bundle-hardlink-test', array $params = [], $locale = null)
    {
        $hardlink = $this->generateHardlink($source, $key, $params, $locale);

        try {
            $hardlink->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving hardlink. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Hardlink::class, Document\Hardlink::getById($hardlink->getId()));

        return $hardlink;
    }

    /**
     * Actor Function to create a child Hardlink
     *
     * @param Document      $parent
     * @param Document\Page $source
     * @param string        $key
     * @param array         $params
     * @param string        $locale
     *
     * @return Document\Hardlink
     */
    public function haveASubHardLink(Document $parent, Document\Page $source, $key = 'bundle-sub-hardlink-test', array $params = [], $locale = null)
    {
        $hardlink = $this->generateHardlink($source, $key, $params, $locale);
        $hardlink->setParent($parent);

        try {
            $hardlink->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving sub hardlink. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Hardlink::class, Document\Hardlink::getById($hardlink->getId()));

        return $hardlink;
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
     * Actor Function to create a Site Document
     *
     * @param string $siteKey
     * @param array  $params
     * @param null   $locale
     * @param bool   $add3w
     * @param array  $additionalDomains
     *
     * @return Site
     */
    public function haveASite($siteKey, array $params = [], $locale = null, $add3w = false, $additionalDomains = [])
    {
        $site = $this->generateSiteDocument($siteKey, $params, $locale, $add3w, $additionalDomains);

        try {
            $site->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving site. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Site::class, Site::getById($site->getId()));

        return $site;
    }

    /**
     * Actor Function to create a Document for a Site
     *
     * @param Site        $site
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Page
     * @throws \Exception
     */
    public function haveAPageDocumentForSite(Site $site, $key = 'document-test', array $params = [], $locale = null)
    {
        $document = $this->generatePageDocument($key, $params, $locale);
        $document->setParentId($site->getRootDocument()->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while document page for site. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Page::class, Document\Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Hard Link for a Site
     *
     * @param Site          $site
     * @param Document\Page $document
     * @param string        $key
     * @param array         $params
     * @param string        $locale
     *
     * @return Document\Hardlink
     */
    public function haveAHardlinkForSite(Site $site, Document\Page $document, $key = 'hardlink-test', array $params = [], $locale = null)
    {
        $hardLink = $this->generateHardlink($document, $key, $params, $locale);
        $hardLink->setParentId($site->getRootDocument()->getId());

        try {
            $hardLink->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while document page for site. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Hardlink::class, Document\Hardlink::getById($hardLink->getId()));

        return $hardLink;
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
        if (!$document instanceof Document\Snippet && !$document instanceof Document\Page) {
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

        if (method_exists($document, 'setMissingRequiredEditable')) {
            $document->setMissingRequiredEditable(false);
        }

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while adding editables to document. message was: ' . $e->getMessage()));
        }

        \Pimcore::collectGarbage();

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

        if (method_exists($document, 'setMissingRequiredEditable')) {
            $document->setMissingRequiredEditable(false);
        }

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while adding area element to document. message was: ' . $e->getMessage()));
        }

        \Pimcore::collectGarbage();

        $this->assertCount(count($editables), VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0') ? $document->getEditables() : $document->getElements());
    }

    /**
     * Actor Function to create a language connection
     *
     * @param Document\Page $sourceDocument
     * @param Document\Page $targetDocument
     *
     */
    public function haveTwoConnectedDocuments(Document\Page $sourceDocument, Document\Page $targetDocument)
    {
        $service = new Document\Service();
        $service->addTranslation($sourceDocument, $targetDocument);
    }

    /**
     * Actor Function to disable a document
     *
     * @param Document $document
     *
     * @return Document
     */
    public function haveAUnPublishedDocument(Document $document)
    {
        if (method_exists($document, 'setMissingRequiredEditable')) {
            $document->setMissingRequiredEditable(false);
        }

        $document->setPublished(false);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while un-publishing document. message was: ' . $e->getMessage()));
        }

        return $document;
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
     * Actor Function to generate a translation for website catalog
     *
     * @param string $key
     * @param string $translation
     * @param string $language
     *
     * @return Website|null
     */
    public function haveAFrontendTranslatedKey(string $key, string $translation, string $language)
    {
        $t = null;

        try {
            /** @var Translator $translator */
            $t = Website::getByKey($key, true);
            $t->addTranslation($language, $translation);
            $t->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating translation. message was: ' . $e->getMessage()));
        }

        $this->assertInstanceOf(Website::class, $t);

        return $t;
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
     * Actor Function to generate a single pimcore redirect.
     *
     * @param array $data
     *
     * @return Redirect
     */
    public function haveAPimcoreRedirect(array $data)
    {
        $redirect = new Redirect();
        $redirect->setValues($data);
        $redirect->save();

        return $redirect;
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
     * @param Document $document
     *
     * @throws ModuleException
     */
    public function submitDocumentToXliffExporter(Document $document)
    {
        /** @var PimcoreCore $pimcoreCore */
        $pimcoreCore = $this->getModule('\\' . PimcoreCore::class);

        $pimcoreCore->_loadPage('POST', '/admin/translation/xliff-export', [
            'csrfToken' => PhpBrowser::PIMCORE_ADMIN_CSRF_TOKEN_NAME,
            'source'    => 'en',
            'target'    => 'de',
            'data'      => json_encode([
                [
                    'id'       => $document->getId(),
                    'path'     => $document->getFullPath(),
                    'type'     => 'document',
                    'children' => true
                ]
            ]),
            'type'      => 'xliff'
        ]);

        $this->assertContains(['success' => true], json_decode($pimcoreCore->_getResponseContent(), true));
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
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Page
     * @throws \Exception
     */
    public function generatePageDocument($key = 'test-page', $params = [], $locale = null)
    {
        if (!isset($params['controller'])) {
            $params['controller'] = '@AppBundle\Controller\DefaultController';
        }

        if (!isset($params['action'])) {
            $params['action'] = 'default';
        }

        $document = TestHelper::createEmptyDocumentPage('', false);

        $document->setKey($key);
        $document->setPublished(true);
        $document->setProperty('navigation_title', 'text', $key);
        $document->setProperty('navigation_name', 'text', $key);

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, true);
        }

        if (method_exists($document, 'setMissingRequiredEditable')) {
            $document->setMissingRequiredEditable(false);
        }

        $this->assignMethods($document, $params);

        return $document;
    }

    /**
     * API Function to create a Snippet
     *
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return null|Document\Snippet
     * @throws \Exception
     */
    public function generateSnippet($key = 'test-snippet', $params = [], $locale = null)
    {
        $document = new Document\Snippet();

        $document->setType('snippet');
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setPublished(true);

        $document->setKey($key);

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, 1);
        }

        $this->assignMethods($document, $params);

        return $document;
    }

    /**
     * API Function to create a email document
     *
     * @param string      $key
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Email
     */
    public function generateEmailDocument($key = 'test-email', array $params = [], $locale = null)
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
        $document->setProperty('test_identifier', 'text', $documentKey, false, false);

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, 1);
        }

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
     * API Function to create a link document
     *
     * @param Document\Page $source
     * @param string        $key
     * @param array         $params
     * @param string        $locale
     *
     * @return Document\Link
     */
    public function generateLink(Document\Page $source, $key = 'test-link', array $params = [], $locale = null)
    {
        $link = new Document\Link();
        $link->setKey($key);
        $link->setPublished(true);
        $link->setParentId(1);
        $link->setLinktype('internal');
        $link->setInternalType('document');
        $link->setInternal($source->getId());

        $link->setProperty('navigation_title', 'text', $key);
        $link->setProperty('navigation_name', 'text', $key);

        if ($locale !== null) {
            $link->setProperty('language', 'text', $locale, false, true);
        }

        if (isset($params['properties'])) {
            $link->setProperties($params['properties']);
            unset($params['properties']);
        }

        $this->assignMethods($link, $params);

        return $link;
    }

    /**
     * API Function to create a hardlink document
     *
     * @param Document\Page $source
     * @param string        $key
     * @param array         $params
     * @param string        $locale
     *
     * @return Document\Hardlink
     */
    public function generateHardlink(Document\Page $source, $key = 'test-hardlink', array $params = [], $locale = null)
    {
        $hardlink = new Document\Hardlink();
        $hardlink->setKey($key);
        $hardlink->setPublished(true);
        $hardlink->setParentId(1);
        $hardlink->setSourceId($source->getId());
        $hardlink->setPropertiesFromSource(true);
        $hardlink->setChildrenFromSource(true);

        if ($locale !== null) {
            $hardlink->setProperty('language', 'text', $locale, false, true);
        }

        if (isset($params['properties'])) {
            $hardlink->setProperties($params['properties']);
            unset($params['properties']);
        }

        $this->assignMethods($hardlink, $params);

        return $hardlink;
    }

    /**
     * API Function to create a site document
     *
     * @param string      $domain
     * @param array       $params
     * @param null|string $locale
     * @param bool        $add3w
     * @param array       $additionalDomains
     *
     * @return Site
     */
    public function generateSiteDocument(string $domain, array $params = [], $locale = null, $add3w = false, $additionalDomains = [])
    {
        $document = TestHelper::createEmptyDocumentPage($domain, false);
        $document->setProperty('navigation_title', 'text', $domain);
        $document->setProperty('navigation_name', 'text', $domain);
        $document->setPublished(true);

        $document->setKey(str_replace('.', '-', $domain));

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, true);
        }

        if (method_exists($document, 'setMissingRequiredEditable')) {
            $document->setMissingRequiredEditable(false);
        }

        if (isset($params['properties'])) {
            $document->setProperties($params['properties']);
            unset($params['properties']);
        }

        $this->assignMethods($document, $params);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving document for site. message was: ' . $e->getMessage()));
        }

        $site = new Site();
        $site->setRootId((int) $document->getId());
        $site->setMainDomain(($add3w ? 'www.' : '') . $domain);

        if (count($additionalDomains) > 0) {
            $site->setDomains($additionalDomains);
        }

        return $site;
    }

    /**
     * API Function to create a asset element
     *
     * @param string $key
     * @param array  $params
     *
     * @return Asset
     */
    public function generateAsset($key = 'test-asset', array $params = [])
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
    public function generateObject(string $objectType, $key = 'test-object', array $params = [])
    {
        $type = sprintf('\\Pimcore\\Model\\DataObject\\%s', $objectType);
        $object = TestHelper::createEmptyObject($key, true, false, $type);

        $object->setKey($key);
        $object->setPublished(true);

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
