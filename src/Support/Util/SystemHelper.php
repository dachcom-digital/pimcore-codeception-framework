<?php

namespace Dachcom\Codeception\Support\Util;

use Codeception\Util\Debug;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Util\TestHelper;

class SystemHelper
{
    public static function pimcoreBundleIsInstalled(string $bundleName): bool
    {
        try {
            \Pimcore::getKernel()->getBundle($bundleName);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    public static function cleanUp(array $tablesToTruncate = []): void
    {
        TestHelper::cleanUp();
        FileGeneratorHelper::cleanUp();

        // we need to reset the current site by using a reflection class
        // read more about it here: https://github.com/pimcore/pimcore/issues/16063
        if (Site::isSiteRequest()) {
            $reflectionClass = new \ReflectionClass(Site::class);
            $reflectionClass->setStaticPropertyValue('currentSite', null);
        }

        // also delete all sub objects
        $objectList = new DataObject\Listing();
        $objectList->setCondition('id != 1');
        $objectList->setUnpublished(true);

        foreach ($objectList->getObjects() as $object) {
            Debug::debug('[TEST BUNDLE] Deleting object: ' . $object->getKey());
            $object->delete();
        }

        // also delete all sub documents
        $docList = new Document\Listing();
        $docList->setCondition('id != 1');
        $docList->setUnpublished(true);

        foreach ($docList->getDocuments() as $document) {
            Debug::debug('[TEST BUNDLE] Deleting document: ' . $document->getKey());
            $document->delete();
        }

        // remove all sites (pimcore < 5.6)
        $db = \Pimcore\Db::get();
        $availableSites = $db->fetchAllAssociative('SELECT * FROM sites');
        if (is_array($availableSites)) {
            foreach ($availableSites as $availableSite) {
                Debug::debug('[TEST BUNDLE] Deleting site: ' . $availableSite['id']);
                $db->delete('sites', ['id' => $availableSite['id']]);
            }
        }

        // remove all redirects
        if (self::pimcoreBundleIsInstalled('PimcoreSeoBundle') === true) {
            $redirects = new \Pimcore\Bundle\SeoBundle\Model\Redirect\Listing();
            foreach ($redirects->getRedirects() as $redirect) {
                Debug::debug('[TEST BUNDLE] Deleting redirect: ' . $redirect->getId());
                $redirect->delete();
            }
        }

        if (self::pimcoreBundleIsInstalled('PimcoreStaticRoutesBundle') === true) {
            $staticRoutes = new \Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute\Listing();
            foreach ($staticRoutes->getRoutes() as $staticRoute) {
                Debug::debug('[TEST BUNDLE] Deleting static route: ' . $staticRoute->getId());
                try {
                    $staticRoute->delete();
                } catch(\Throwable $e) {
                    Debug::debug('[TEST BUNDLE] Error while trying to delete static route ' . $staticRoute->getId() . ': ' . $e->getMessage());
                }
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
