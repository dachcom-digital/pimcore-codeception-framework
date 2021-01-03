<?php

namespace Dachcom\Codeception\Util;

use Doctrine\DBAL\Exception;
use Pimcore\Model\DataObject;
use Pimcore\Tests\Util\TestHelper;

class SystemHelper
{
    const AREA_TEST_NAMESPACE = 'bundleTestArea';

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

    /**
     * @param string $name
     * @param string $type
     * @param array  $options
     * @param null   $data
     *
     * @return string
     */
    public static function generateEditableConfiguration(string $name, string $type, array $options, $data = null)
    {
        $dotSuffix = VersionHelper::pimcoreVersionIsGreaterOrEqualThan('5.5.0') ? '_' : '.';
        $colonSuffix = VersionHelper::pimcoreVersionIsGreaterOrEqualThan('5.5.0') ? '_' : ':';
        $prettyJson = VersionHelper::pimcoreVersionIsGreaterOrEqualThan('5.5.4');

        $editableConfig = [
            'id'        => sprintf('pimcore_editable_%s%s1%s%s', self::AREA_TEST_NAMESPACE, $colonSuffix, $dotSuffix, $name),
            'name'      => sprintf('%s:1.%s', self::AREA_TEST_NAMESPACE, $name),
            'realName'  => $name,
            'options'   => $options,
            'data'      => $data,
            'type'      => $type,
            'inherited' => false,
        ];

        $data = sprintf('editableConfigurations.push(%s);', json_encode($editableConfig, ($prettyJson ? JSON_PRETTY_PRINT : JSON_ERROR_NONE)));

        return $data;
    }
}
