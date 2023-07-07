<?php

namespace Dachcom\Codeception\Support\Util;

use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Util\TestHelper;

class SystemHelper
{
    public static function cleanUp(array $tablesToTruncate = []): void
    {
        TestHelper::cleanUp();
        FileGeneratorHelper::cleanUp();

        // also delete all sub objects
        $objectList = new DataObject\Listing();
        $objectList->setCondition('id != 1');
        $objectList->setUnpublished(true);

        foreach ($objectList->getObjects() as $object) {
            \Codeception\Util\Debug::debug('[TEST BUNDLE] Deleting object: ' . $object->getKey());
            $object->delete();
        }

        // also delete all sub documents
        $docList = new Document\Listing();
        $docList->setCondition('id != 1');
        $docList->setUnpublished(true);

        foreach ($docList->getDocuments() as $document) {
            \Codeception\Util\Debug::debug('[TEST BUNDLE] Deleting document: ' . $document->getKey());
            $document->delete();
        }

        // remove all sites (pimcore < 5.6)
        $db = \Pimcore\Db::get();
        $availableSites = $db->fetchAllAssociative('SELECT * FROM sites');
        if (is_array($availableSites)) {
            foreach ($availableSites as $availableSite) {
                \Codeception\Util\Debug::debug('[TEST BUNDLE] Deleting site: ' . $availableSite['id']);
                $db->delete('sites', ['id' => $availableSite['id']]);
            }
        }

        // remove all redirects
        if(class_exists('Pimcore\Bundle\SeoBundle\Model\Redirect')) {
            $redirects = new \Pimcore\Bundle\SeoBundle\Model\Redirect\Listing();
            foreach ($redirects->getRedirects() as $redirect) {
                \Codeception\Util\Debug::debug('[TEST BUNDLE] Deleting redirect: ' . $redirect->getId());
                $redirect->delete();
            }
        }

        // remove static routes

        if(class_exists('Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute')) {
            $staticRoutes = new \Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute\Listing();
            foreach ($staticRoutes->getRoutes() as $staticRoute) {
                \Codeception\Util\Debug::debug('[TEST BUNDLE] Deleting static route: ' . $staticRoute->getId());
                $staticRoute->delete();
            }
        }

        if (count($tablesToTruncate) === 0) {
            return;
        }

        $db = \Pimcore\Db::get();
        foreach ($tablesToTruncate as $table) {
            $db->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }
    }
}