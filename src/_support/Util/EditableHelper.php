<?php

namespace Dachcom\Codeception\Util;

use Pimcore\Model\Document\Editable;

class EditableHelper
{
    const AREA_TEST_NAMESPACE = 'bundleTestArea';

    /**
     * @param string $areaName
     * @param array  $editables
     *
     * @return array
     * @throws \Exception
     */
    public static function generateEditablesForArea(string $areaName, array $editables = [])
    {
        if (VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0')) {
            $blockAreaClass = 'Pimcore\Model\Document\Editable\Areablock';
        } else {
            $blockAreaClass = 'Pimcore\Model\Document\Tag\Areablock';
        }

        /** @var Editable $blockArea */
        $blockArea = new $blockAreaClass();
        $blockArea->setName(self::AREA_TEST_NAMESPACE);
        $blockArea->setDataFromEditmode([
            [
                'key'    => '1',
                'type'   => $areaName,
                'hidden' => false
            ]
        ]);

        $editables = self::generateEditables($editables, $blockArea->getName());

        return array_merge([$blockArea->getName() => $blockArea], $editables);
    }

    /**
     * @param array       $editables
     * @param string|null $editableNamespace
     *
     * @return array
     * @throws \Exception
     */
    public static function generateEditables(array $editables = [], ?string $editableNamespace = null)
    {
        if (VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0')) {
            $namespace = 'Pimcore\Model\Document\Editable';
        } else {
            $namespace = 'Pimcore\Model\Document\Tag';
        }

        if (count($editables) === 0) {
            return [];
        }

        $elements = [];
        foreach ($editables as $editableName => $editableConfig) {

            $editableType = $editableConfig['type'] ?: 'unknown';
            $elementClass = sprintf('%s\%s', $namespace, ucfirst($editableType));

            if (!class_exists($elementClass)) {
                throw new \Exception(sprintf('Editable type %s does not exist', $elementClass));
            }

            unset($editableConfig['type']);

            /** @var Editable $element */
            $element = new $elementClass();

            if ($editableNamespace === null) {
                $element->setName($editableName);
            } else {
                $element->setName(sprintf('%s:1.%s', $editableNamespace, $editableName));
            }

            foreach ($editableConfig as $config => $configValue) {

                $setter = sprintf('set%s', ucfirst($config));

                if (!method_exists($element, $setter)) {
                    throw new \Exception(sprintf('method %s does not exist in entity %s', $setter, get_class($element)));
                }

                $element->$setter($configValue);
            }

            $elements[$element->getName()] = $element;
        }

        return $elements;
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
        $editableConfig = [
            'id'       => sprintf('pimcore_editable_%s%s1%s%s', self::AREA_TEST_NAMESPACE, '_', '_', $name),
            'name'     => sprintf('%s:1.%s', self::AREA_TEST_NAMESPACE, $name),
            'realName' => $name
        ];

        if (VersionHelper::pimcoreVersionIsGreaterOrEqualThan('6.8.0')) {
            $configName = 'editableDefinitions';
            $editableConfig = array_merge($editableConfig, [
                'config'      => $options,
                'data'        => is_int($data) ? (string) $data : $data,
                'type'        => $type,
                'inherited'   => false,
                'inDialogBox' => null
            ]);

        } else {
            $configName = 'editableConfigurations';
            $editableConfig = array_merge($editableConfig, [
                'options'   => $options,
                'data'      => is_int($data) ? (string) $data : $data,
                'type'      => $type,
                'inherited' => false
            ]);
        }

        $data = sprintf('%s.push(%s);', $configName, json_encode($editableConfig, JSON_PRETTY_PRINT));

        return $data;
    }
}
