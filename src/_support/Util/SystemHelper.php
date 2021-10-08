<?php

namespace Dachcom\Codeception\Util;

use Doctrine\DBAL\Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Tests\Util\TestHelper;

class SystemHelper
{
    public static function cleanUp(array $tablesToTruncate = []): void
    {
        TestHelper::cleanUp();
        FileGeneratorHelper::cleanUp();

         // also delete all sub objects
        $objectList = new DataObject\Listing();
        $objectList->setCondition('o_id != 1');
        $objectList->setUnpublished(true);

        foreach ($objectList->getObjects() as $object) {
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
        $availableSites = $db->fetchAll('SELECT * FROM sites');
        if (is_array($availableSites)) {
            foreach ($availableSites as $availableSite) {
                $db->delete('sites', ['id' => $availableSite['id']]);
            }
        }

        // remove all redirects
        $redirects = new Redirect\Listing();
        foreach ($redirects->load() as $redirect) {
            $redirect->delete();
        }

        if (count($tablesToTruncate) === 0) {
            return;
        }

        $db = \Pimcore\Db::get();
        foreach ($tablesToTruncate as $table) {
            $db->exec(sprintf('TRUNCATE TABLE %s', $table));
        }
    }
}
