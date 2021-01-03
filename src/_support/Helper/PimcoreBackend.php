<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Util\Debug;
use Dachcom\Codeception\Util\VersionHelper;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Model\Document;

class PimcoreBackend extends Module
{
    /**
     * @param TestInterface $test
     */
    public function _before(TestInterface $test)
    {
        parent::_before($test);

        TestHelper::cleanUpTree(Document::getById(1), 'document');
    }

    /**
     * Actor Function to create a Page Document
     *
     * @param string      $documentKey
     * @param array       $params
     * @param null|string $locale
     *
     * @return Document\Page
     * @throws \Exception
     */
    public function haveAPageDocument(
        $documentKey = 'bundle-test',
        $params = [],
        $locale = 'en'
    ) {
        $document = $this->generatePageDocument($documentKey, $params, $locale);

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
     * @param string $snippetName
     * @param array  $params
     * @param array  $elements
     *
     * @throws \Exception
     */
    public function haveASnippet($snippetName, $params = [], $elements = [])
    {
        $this->generateSnippet($snippetName, $params, $elements);
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
     * @param string $snippetName
     * @param array  $params
     * @param array  $editables
     *
     * @return null|Document\Snippet
     * @throws \Exception
     */
    protected function generateSnippet($snippetName, $params = [], $editables = [])
    {
        $document = new Document\Snippet();

        $document->setType('snippet');
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setKey($snippetName);
        $document->setPublished(true);

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

        try {
            $document->save();
        } catch (\Exception $e) {
            $this->debug(sprintf('[PIMCORE BACKEND MODULE]: error while creating snippet: ' . $e->getMessage()));
            return null;
        }

        return $document;
    }
}
