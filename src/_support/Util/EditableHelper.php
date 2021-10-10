<?php

namespace Dachcom\Codeception\Util;

use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Areablock;

class EditableHelper
{
    public const AREA_TEST_NAMESPACE = 'bundleTestArea';

    public static function generateEditablesForArea(string $areaName, array $editables = []): array
    {
        $blockArea = new Areablock();
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

    public static function generateEditables(array $editables = [], ?string $editableNamespace = null): array
    {
        if (count($editables) === 0) {
            return [];
        }

        $elements = [];
        foreach ($editables as $editableName => $editableConfig) {

            $editableType = $editableConfig['type'] ?: 'unknown';
            $elementClass = sprintf('Pimcore\Model\Document\Editable\%s', ucfirst($editableType));

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

    public static function generateEditableConfiguration(string $name, string $type, ?string $label, array $options, $data = null): string
    {
        $editableConfig = [
            'id'       => sprintf('pimcore_editable_%s%s1%s%s', self::AREA_TEST_NAMESPACE, '_', '_', $name),
            'name'     => sprintf('%s:1.%s', self::AREA_TEST_NAMESPACE, $name),
            'realName' => $name
        ];

        if ($label !== null) {
            $options = array_merge($options, [
                'dialogBoxConfig' => [
                    'type'   => $type,
                    'name'   => $name,
                    'label'  => $label,
                    'config' => $options,
                ]
            ]);
        }

        $editableConfig = array_merge($editableConfig, [
            'config'      => $options,
            'data'        => is_int($data) ? (string) $data : $data,
            'type'        => $type,
            'inherited'   => false,
            'inDialogBox' => $label === null ? null : 'dialogBox-bundleTestArea-1',
        ]);

        return sprintf(json_encode($editableConfig, JSON_PRETTY_PRINT));
    }
}
