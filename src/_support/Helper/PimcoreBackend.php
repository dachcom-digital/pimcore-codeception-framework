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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\Redirect;
use Pimcore\Model\Site;
use Pimcore\Model\Staticroute;
use Pimcore\Model\Tool\Email\Log;
use Pimcore\Model\Translation;
use Pimcore\Tests\Helper\ClassManager;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Model\Document;
use Pimcore\Translation\Translator;
use Pimcore\Model\Version;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Serializer\Serializer;

class PimcoreBackend extends Module
{
    public function _before(TestInterface $test)
    {
        FileGeneratorHelper::preparePaths();

        parent::_before($test);
    }

    public function _after(TestInterface $test)
    {
        SystemHelper::cleanUp();
        FileGeneratorHelper::cleanUp();

        parent::_after($test);
    }

    /**
     * Actor Function to create a Page Document
     */
    public function haveAPageDocument(string $key = 'bundle-page-test', array $params = [], ?string $locale = null): Document\Page
    {
        $document = $this->generatePageDocument($key, $params, $locale);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving document page. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Page::class, Document\Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Child Page Document
     */
    public function haveASubPageDocument(Document $parent, string $key = 'bundle-sub-page-test', array $params = [], ?string $locale = null): Document\Page
    {
        $document = $this->generatePageDocument($key, $params, $locale);
        $document->setParentId($parent->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving child document page. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Page::class, Document\Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a language connection
     */
    public function haveTwoConnectedDocuments(Document\Page $sourceDocument, Document\Page $targetDocument): void
    {
        $service = new Document\Service();
        $service->addTranslation($sourceDocument, $targetDocument);
    }

    /**
     * Actor Function to disable a document
     */
    public function haveAUnPublishedDocument(Document $document): Document
    {
        if (method_exists($document, 'setMissingRequiredEditable')) {
            $document->setMissingRequiredEditable(false);
        }

        $document->setPublished(false);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while un-publishing document. message was: %s', $e->getMessage()));
        }

        return $document;
    }

    /**
     * Actor Function to move a document
     */
    public function moveDocument(Document $document, Document $parentDocument): Document
    {
        $document->setParent($parentDocument);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while moving document. message was: %s', $e->getMessage()));
        }

        $this->assertEquals($parentDocument->getId(), $document->getParentId());

        return $document;
    }

    /**
     * Actor Function to create a Snippet
     */
    public function haveASnippet(string $key = 'bundle-snippet-test', array $params = [], ?string $locale = null): Document\Snippet
    {
        $document = $this->generateSnippet($key, $params, $locale);

        try {
            $document->save();
        } catch (\Exception $e) {
            $this->debug(sprintf('[TEST BUNDLE ERROR]: error while saving snippet: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Snippet::class, Document\Snippet::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a mail document
     */
    public function haveAEmail(string $key = 'bundle-email-test', array $params = [], ?string $locale = null): Document\Email
    {
        $document = $this->generateEmailDocument($key, $params, $locale);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating email. message was: %s', $e->getMessage()));
        }

        // needed?
        //\Pimcore\Cache\Runtime::set(sprintf('document_%s', $document->getId()), null);

        $this->assertInstanceOf(Document\Email::class, Document\Email::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a link
     */
    public function haveALink(Document\Page $source, string $key = 'bundle-link-test', array $params = [], ?string $locale = null): Document\Link
    {
        $link = $this->generateLink($source, $key, $params, $locale);

        try {
            $link->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving link. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Link::class, Document\Link::getById($link->getId()));

        return $link;
    }

    /**
     * Actor Function to create a link
     */
    public function haveASubLink(Document $parent, Document\Page $source, string $key = 'bundle-sub-link-test', array $params = [], ?string $locale = null): Document\Link
    {
        $link = $this->generateLink($source, $key, $params, $locale);
        $link->setParent($parent);

        try {
            $link->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving sub link. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Link::class, Document\Link::getById($link->getId()));

        return $link;
    }

    /**
     * Actor Function to create a Hardlink
     */
    public function haveAHardLink(Document\Page $source, string $key = 'bundle-hardlink-test', array $params = [], ?string $locale = null): Document\Hardlink
    {
        $hardlink = $this->generateHardlink($source, $key, $params, $locale);

        try {
            $hardlink->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving hardlink. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Hardlink::class, Document\Hardlink::getById($hardlink->getId()));

        return $hardlink;
    }

    /**
     * Actor Function to create a child Hardlink
     */
    public function haveASubHardLink(
        Document $parent,
        Document\Page $source,
        string $key = 'bundle-sub-hardlink-test',
        array $params = [],
        ?string $locale = null
    ): Document\Hardlink {
        $hardlink = $this->generateHardlink($source, $key, $params, $locale);
        $hardlink->setParent($parent);

        try {
            $hardlink->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving sub hardlink. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Hardlink::class, Document\Hardlink::getById($hardlink->getId()));

        return $hardlink;
    }

    /**
     * Actor Function to create a pimcore object
     */
    public function haveAPimcoreObject(string $objectType, string $key = 'bundle-object-test', array $params = []): DataObject\Concrete
    {
        $object = $this->generateObject($objectType, $key, $params);

        try {
            $object->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating object. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(get_class($object), DataObject::getById($object->getId()));

        return $object;
    }

    /**
     * Actor Function to create a child object
     */
    public function haveASubPimcoreObject(DataObject $parent, string $objectType, string $key = 'bundle-sub-object-test', array $params = []): DataObject\Concrete
    {
        $object = $this->generateObject($objectType, $key, $params);
        $object->setParentId($parent->getId());

        try {
            $object->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving child object. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(get_class($object), DataObject::getById($object->getId()));

        return $object;
    }

    /**
     * Actor Function to refresh an object
     */
    public function refreshObject(DataObject $object): DataObject
    {
        $reloadedObject = DataObject::getById($object->getId(), true);

        $this->assertEquals($reloadedObject->getId(), $object->getId());

        return $reloadedObject;
    }

    /**
     * Actor Function to move an object
     */
    public function moveObject(DataObject $object, DataObject $parentObject): DataObject
    {
        $object->setParent($parentObject);

        try {
            $object->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while moving object. message was: %s', $e->getMessage()));
        }

        $this->assertEquals($parentObject->getId(), $object->getParentId());

        return $object;
    }

    /**
     * Actor Function to copy object
     */
    public function copyObject(DataObject $object, DataObject $targetObject): DataObject
    {
        $objectService = new DataObject\Service();

        $newObject = $objectService->copyAsChild($targetObject, $object);

        $this->assertInstanceOf(DataObject::class, $newObject);

        return $newObject;
    }

    /**
     * Actor Function to create an object version only
     */
    public function createNewObjectVersion(DataObject\Concrete $object): Version
    {
        $object->saveVersion();

        return $object->getLatestVersion(true);
    }

    /**
     * Actor Function to delete a object version
     */
    public function deleteObjectVersion(Version $version): void
    {
        $version->delete();
    }

    /**
     * Actor Function to publish an object version
     *
     * @return DataObject
     */
    public function publishObjectVersion(Version $version): DataObject
    {
        $version = Version::getById($version->getId());

        $data = $version->loadData();

        $data->save();

        return $data;
    }

    /**
     * Actor Function to move object to bin
     */
    public function moveObjectToRecycleBin(DataObject $object): Item
    {
        $item = new Item();
        $item->setElement($object);
        $item->save();

        $object->delete();

        $deletedObject = DataObject::getById($object->getId(), true);

        $this->assertNull($deletedObject);

        return $item;
    }

    /**
     * Actor Function to restore an object from bin
     */
    public function restoreObjectFromRecycleBin(DataObject $object, Item $item): DataObject
    {
        $item->restore();

        $restoredObject = DataObject::getById($object->getId(), true);

        $this->assertInstanceOf(DataObject::class, $restoredObject);

        return $restoredObject;
    }

    /**
     * Actor Function to create a pimcore object folder
     */
    public function haveAPimcoreObjectFolder(string $key = 'bundle-object-folder-test', array $params = []): DataObject\Folder
    {
        $assetFolder = $this->generateFolder($key, 'object', $params);

        try {
            $assetFolder->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating object folder. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(DataObject\Folder::class, DataObject\Folder::getById($assetFolder->getId()));

        return $assetFolder;
    }

    /**
     * Actor Function to create a pimcore asset
     */
    public function haveAPimcoreAsset(string $key = 'bundle-asset-test', array $params = []): Asset
    {
        $asset = $this->generateAsset($key, $params);

        try {
            $asset->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating asset. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Asset::class, Asset::getById($asset->getId()));

        return $asset;
    }

    /**
     * Actor Function to create a child asset
     */
    public function haveASubPimcoreAsset(Asset\Folder $parent, string $key = 'bundle-sub-asset-test', array $params = []): Asset
    {
        $asset = $this->generateAsset($key, $params);
        $asset->setParentId($parent->getId());

        try {
            $asset->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving child asset. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Asset::class, Asset::getById($asset->getId()));

        return $asset;
    }

    /**
     * Actor Function to create a pimcore asset folder
     */
    public function haveAPimcoreAssetFolder(string $key = 'bundle-asset-folder-test', array $params = []): Asset\Folder
    {
        $assetFolder = $this->generateFolder($key, 'asset', $params);

        try {
            $assetFolder->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating asset folder. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Asset\Folder::class, Asset\Folder::getById($assetFolder->getId()));

        return $assetFolder;
    }

    /**
     * Actor Function to create a pimcore asset sub folder
     */
    public function haveASubPimcoreAssetFolder(Asset\Folder $parent, string $key = 'bundle-asset-sub-folder-test', array $params = []): Asset\Folder
    {
        $assetFolder = $this->generateFolder($key, 'asset', $params);
        $assetFolder->setParentId($parent->getId());

        try {
            $assetFolder->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating asset folder. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Asset\Folder::class, Asset\Folder::getById($assetFolder->getId()));

        return $assetFolder;
    }

    /**
     * Actor Function to move a asset
     */
    public function moveAsset(Asset $asset, Asset $parentAsset): Asset
    {
        $asset->setParent($parentAsset);

        try {
            $asset->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while moving asset. message was: %s', $e->getMessage()));
        }

        $this->assertEquals($parentAsset->getId(), $asset->getParentId());

        return $asset;
    }

    /**
     * Actor function to generate a dummy asset file
     */
    public function haveADummyFile(string $fileName, int $fileSizeInMb = 1)
    {
        FileGeneratorHelper::generateDummyFile($fileName, $fileSizeInMb);
    }

    /**
     * Actor Function to create a Site Document
     */
    public function haveASite($siteKey, array $params = [], ?string $locale = null, bool $add3w = false, array $additionalDomains = []): Site
    {
        $site = $this->generateSiteDocument($siteKey, $params, $locale, $add3w, $additionalDomains);

        try {
            $site->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving site. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Site::class, Site::getById($site->getId()));

        return $site;
    }

    /**
     * Actor Function to create a Document for a Site
     */
    public function haveAPageDocumentForSite(Site $site, string $key = 'document-test', array $params = [], ?string $locale = null): Document\Page
    {
        $document = $this->generatePageDocument($key, $params, $locale);
        $document->setParentId($site->getRootDocument()->getId());

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while document page for site. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Page::class, Document\Page::getById($document->getId()));

        return $document;
    }

    /**
     * Actor Function to create a Hard Link for a Site
     */
    public function haveAHardlinkForSite(
        Site $site,
        Document\Page $document,
        string $key = 'hardlink-test',
        array $params = [],
        ?string $locale = null
    ): Document\Hardlink {
        $hardLink = $this->generateHardlink($document, $key, $params, $locale);
        $hardLink->setParentId($site->getRootDocument()->getId());

        try {
            $hardLink->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while document page for site. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Document\Hardlink::class, Document\Hardlink::getById($hardLink->getId()));

        return $hardLink;
    }

    /**
     * Actor function to see a generated dummy file in download directory
     */
    public function seeDownload(string $fileName): void
    {
        $supportDir = FileGeneratorHelper::getDownloadPath();
        $filePath = $supportDir . $fileName;

        $this->assertTrue(is_file($filePath));
    }

    /**
     * Actor to place editables on document
     */
    public function seeEditablesPlacedOnDocument(Document $document, array $editables): void
    {
        if (!$document instanceof Document\Snippet && !$document instanceof Document\Page) {
            throw new ModuleException($this, sprintf('%s must be instance of %s or %s', $document->getFullPath(), Document\Snippet::class, Document\Page::class));
        }

        try {
            $editables = EditableHelper::generateEditables($editables);
        } catch (\Throwable $e) {
            throw new ModuleException($this, sprintf('editable generator error: %s', $e->getMessage()));
        }

        $document->setEditables($editables);
        $document->setMissingRequiredEditable(false);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while adding editables to document. message was: %s', $e->getMessage()));
        }

        \Pimcore::collectGarbage();

        $this->assertCount(count($editables), $document->getEditables());
    }

    /**
     * Actor Function to place a area on a document
     */
    public function seeAnAreaElementPlacedOnDocument(Document $document, string $areaName, array $editables = []): void
    {
        if (!$document instanceof Document\Snippet && !$document instanceof Document\Page) {
            throw new ModuleException($this, sprintf('%s must be instance of %s or %s.', $document->getFullPath(), Document\Snippet::class, Document\Page::class));
        }

        try {
            $editables = EditableHelper::generateEditablesForArea($areaName, $editables);
        } catch (\Throwable $e) {
            throw new ModuleException($this, sprintf('area generator error: %s', $e->getMessage()));
        }

        $document->setEditables($editables);
        $document->setMissingRequiredEditable(false);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while adding area element to document. message was: %s', $e->getMessage()));
        }

        \Pimcore::collectGarbage();

        $this->assertCount(count($editables), $document->getEditables());
    }

    /**
     * Actor Function to see if given email has been sent
     */
    public function seeEmailIsSent(Document\Email $email): void
    {
        $this->assertInstanceOf(Document\Email::class, $email);

        $foundEmails = $this->getEmailsFromDocumentIds([$email->getId()]);
        $this->assertEquals(1, count($foundEmails));
    }

    /**
     * Actor Function to see if an email has been sent to admin
     */
    public function seeEmailIsNotSent(Document\Email $email): void
    {
        $this->assertInstanceOf(Document\Email::class, $email);

        $foundEmails = $this->getEmailsFromDocumentIds([$email->getId()]);
        $this->assertEquals(0, count($foundEmails));
    }

    /**
     * Actor Function to see if admin email contains given properties
     */
    public function seePropertiesInEmail(Document\Email $mail, array $properties): void
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        $serializer = $this->getSerializer();

        foreach ($foundEmails as $email) {
            $params = $serializer->decode($email->getParams(), 'json', ['json_decode_associative' => true]);
            foreach ($properties as $propertyKey => $propertyValue) {
                $key = array_search($propertyKey, array_column($params, 'key'), true);
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
     */
    public function seePropertyKeysInEmail(Document\Email $mail, array $properties): void
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        $serializer = $this->getSerializer();

        foreach ($foundEmails as $email) {
            $params = $serializer->decode($email->getParams(), 'json', ['json_decode_associative' => true]);
            foreach ($properties as $propertyKey) {
                $key = array_search($propertyKey, array_column($params, 'key'), true);
                $this->assertNotSame(false, $key);
            }
        }
    }

    /**
     * Actor Function to see if admin email not contains given properties
     */
    public function cantSeePropertyKeysInEmail(Document\Email $mail, array $properties): void
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        $serializer = $this->getSerializer();

        foreach ($foundEmails as $email) {
            $params = $serializer->decode($email->getParams(), 'json', ['json_decode_associative' => true]);
            foreach ($properties as $propertyKey) {
                $this->assertFalse(
                    array_search($propertyKey, array_column($params, 'key'), true),
                    sprintf('Failed asserting that search for "%s" is false.', $propertyKey)
                );
            }
        }
    }

    /**
     * Actor Function to see rendered body text in given email
     */
    public function seeInRenderedEmailBody(Document\Email $mail, string $string): void
    {
        $this->assertInstanceOf(Document\Email::class, $mail);

        $foundEmails = $this->getEmailsFromDocumentIds([$mail->getId()]);
        $this->assertGreaterThan(0, count($foundEmails));

        foreach ($foundEmails as $email) {
            $bodyHtml = $email->getHtmlLog();
            $this->assertStringContainsString($string, $bodyHtml);
        }
    }

    /**
     * Actor Function to see if a key has been stored in admin translations
     */
    public function seeKeyInFrontendTranslations(string $key): void
    {
        /** @var Translator $translator */
        $translator = \Pimcore::getContainer()->get('translator');
        $this->assertTrue($translator->getCatalogue()->has($key));
    }

    /**
     * Actor Function to generate a translation for website catalog
     */
    public function haveAFrontendTranslatedKey(string $key, string $translation, string $language): ?Translation
    {
        $t = null;

        try {
            /** @var Translator $translator */
            $t = Translation::getByKey($key, 'messages', true);
            $t->addTranslation($language, $translation);
            $t->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while creating translation. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Translation::class, $t);

        return $t;
    }

    /**
     * Actor Function to generate a single static route
     */
    public function haveAStaticRoute(string $name = 'test_route', array $params = []): Staticroute
    {
        $defaults = [
            'name'             => $name,
            'controller'       => sprintf('App\\Controller\\DefaultController::%s', $params['action'] ?? 'defaultAction'),
            'defaults'         => null,
            'siteId'           => [],
            'priority'         => 0,
            'methods'          => null,
            'creationDate'     => 1545383519,
            'modificationDate' => 1545383619
        ];

        unset($params['action']);

        $data = array_merge($defaults, $params);

        $route = new Staticroute();
        $route->setValues($data);
        $route->save();

        $this->assertInstanceOf(Staticroute::class, $route);

        return $route;
    }

    /**
     * Actor Function to generate a single pimcore redirect
     */
    public function haveAPimcoreRedirect(array $data): Redirect
    {
        $redirect = new Redirect();
        $redirect->setValues($data);
        $redirect->save();

        return $redirect;
    }

    /**
     * Actor Function to generate a pimcore class from json definition file
     *
     * @throws ModuleException
     */
    public function haveAPimcoreClass(string $name = 'TestClass'): DataObject\ClassDefinition
    {
        $cm = $this->getClassManager();

        $bundleClass = getenv('TEST_BUNDLE_TEST_DIR');
        $path = sprintf('%s/_etc/classes', $bundleClass);

        $class = $cm->setupClass($name, sprintf('%s/%s.json', $path, $name));
        $this->assertInstanceOf(DataObject\ClassDefinition::class, $class);

        return $class;
    }

    /**
     * Actor Function to submit document to xliff exporter
     *
     * @throws \Exception
     */
    public function submitDocumentToXliffExporter(Document $document): void
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
            ], JSON_THROW_ON_ERROR),
            'type'      => 'xliff'
        ]);

        $this->assertSame(['success' => true], json_decode($pimcoreCore->_getResponseContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * API Function to get sent email ids from given document ids
     *
     * @public to allow usage from other modules
     *
     * @return Log[]
     */
    public function getEmailsFromDocumentIds(array $documentIds): array
    {
        $emailLogs = new Log\Listing();
        $emailLogs->addConditionParam(sprintf('documentId IN (%s)', implode(',', $documentIds)));

        return $emailLogs->load();
    }

    /**
     * API Function to get pimcore serializer
     *
     * @public to allow usage from other modules
     */
    public function getSerializer(): Serializer
    {
        $serializer = null;

        try {
            $serializer = $this->getContainer()->get('pimcore_admin.serializer');
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while getting pimcore admin serializer. message was: %s', $e->getMessage()));
        }

        $this->assertInstanceOf(Serializer::class, $serializer);

        return $serializer;
    }

    /**
     * API Function to create a page document
     */
    public function generatePageDocument(string $key = 'test-page', array $params = [], ?string $locale = null): Document\Page
    {
        $controller = sprintf('%s::%s',
            $params['controller'] ?? 'App\Controller\DefaultController',
            $params['action'] ?? 'defaultAction',
        );

        unset($params['controller'], $params['action']);

        $document = TestHelper::createEmptyDocumentPage('', false);

        $document->setKey($key);
        $document->setController($controller);
        $document->setPublished(true);
        $document->setProperty('navigation_title', 'text', $key);
        $document->setProperty('navigation_name', 'text', $key);

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, true);
        }

        $document->setMissingRequiredEditable(false);

        $this->assignMethods($document, $params);

        return $document;
    }

    /**
     * API Function to create a Snippet
     */
    public function generateSnippet(string $key = 'test-snippet', array $params = [], ?string $locale = null): Document\Snippet
    {
        $controller = sprintf('%s::%s',
            $params['controller'] ?? 'App\Controller\SnippetController',
            $params['action'] ?? 'defaultAction',
        );

        unset($params['controller'], $params['action']);

        $document = new Document\Snippet();
        $document->setKey($key);
        $document->setController($controller);
        $document->setType('snippet');
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setPublished(true);
        $document->setMissingRequiredEditable(false);

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, 1);
        }

        $this->assignMethods($document, $params);

        return $document;
    }

    /**
     * API Function to create a email document
     */
    public function generateEmailDocument(string $key = 'test-email', array $params = [], ?string $locale = null): Document\Email
    {
        $controller = sprintf('%s::%s',
            $params['controller'] ?? 'App\Controller\EmailController',
            $params['action'] ?? 'defaultAction',
        );

        unset($params['controller'], $params['action']);

        $documentKey = uniqid(sprintf('%s-', $key), true);

        $document = new Document\Email();
        $document->setKey($documentKey);
        $document->setController($controller);
        $document->setType('email');
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setPublished(true);
        $document->setMissingRequiredEditable(false);

        $document->setProperty('test_identifier', 'text', $documentKey, false, false);

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, 1);
        }

        if (!isset($params['to'])) {
            $params['to'] = 'recpient@test.org';
        }

        if (!isset($params['subject'])) {
            $params['subject'] = 'TEST BUNDLE';
        }

        $params['subject'] = sprintf('[%s] %s', $documentKey, $params['subject']);

        if (isset($params['properties'])) {
            $document->setProperties($params['properties']);
            unset($params['properties']);
        }

        $this->assignMethods($document, $params);

        return $document;
    }

    /**
     * API Function to create a link document
     */
    public function generateLink(Document\Page $source, string $key = 'test-link', array $params = [], ?string $locale = null): Document\Link
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
     */
    public function generateHardlink(Document\Page $source, string $key = 'test-hardlink', array $params = [], ?string $locale = null): Document\Hardlink
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
     */
    public function generateSiteDocument(string $domain, array $params = [], ?string $locale = null, bool $add3w = false, array $additionalDomains = []): Site
    {
        $document = TestHelper::createEmptyDocumentPage($domain, false);
        $document->setProperty('navigation_title', 'text', $domain);
        $document->setProperty('navigation_name', 'text', $domain);
        $document->setPublished(true);

        $document->setKey(str_replace('.', '-', $domain));

        if ($locale !== null) {
            $document->setProperty('language', 'text', $locale, false, true);
        }

        $document->setMissingRequiredEditable(false);

        if (isset($params['properties'])) {
            $document->setProperties($params['properties']);
            unset($params['properties']);
        }

        $this->assignMethods($document, $params);

        try {
            $document->save();
        } catch (\Exception $e) {
            Debug::debug(sprintf('[TEST BUNDLE ERROR] error while saving document for site. message was: %s' . $e->getMessage()));
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
     */
    public function generateAsset(string $key = 'test-asset', array $params = []): Asset
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
     */
    public function generateObject(string $objectType, string $key = 'test-object', array $params = []): DataObject\Concrete
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
     * API Function to create a folder based on type
     */
    public function generateFolder(string $key = 'test-asset-folder', string $type = 'asset', array $params = []): Asset\Folder|Document\Folder|DataObject\Folder
    {
        if ($type === 'document') {
            $folder = TestHelper::createDocumentFolder($key, false);
        } elseif ($type === 'object') {
            $folder = TestHelper::createObjectFolder($key, false);
        } elseif ($type === 'asset') {
            $folder = TestHelper::createAssetFolder($key, false);
        }

        $folder->setKey($key);

        if (isset($params['properties'])) {
            $folder->setProperties($params['properties']);
            unset($params['properties']);
        }

        $this->assignMethods($folder, $params);

        return $folder;
    }

    protected function getContainer(): Container
    {
        return $this->getModule('\\' . PimcoreCore::class)->getContainer();
    }

    protected function getClassManager(): ClassManager
    {
        return $this->getModule('\\' . ClassManager::class);
    }

    protected function assignMethods(ElementInterface $entity, array $params): void
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
