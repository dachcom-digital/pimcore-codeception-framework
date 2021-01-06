<?php

namespace Dachcom\Codeception\Util;

use Doctrine\DBAL\Exception;
use Pimcore\Model\DataObject;
use Pimcore\Tests\Util\TestHelper;

class SystemHelper
{
    /**
     * @param array $tablesToTruncate
     *
     * @throws Exception
     */
    public static function cleanUp(array $tablesToTruncate = [])
    {
        TestHelper::cleanUp();
        FileGeneratorHelper::cleanUp();

        $objectList = new DataObject\Listing();
        $objectList->setCondition('o_id != 1');
        $objectList->setUnpublished(true);

        foreach ($objectList->getObjects() as $object) {
            $object->delete();
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
