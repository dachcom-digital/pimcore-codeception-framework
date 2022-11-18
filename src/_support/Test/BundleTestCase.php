<?php

namespace Dachcom\Codeception\Test;

use Codeception\Exception\ModuleException;
use Dachcom\Codeception\Helper\PimcoreCore;
use Dachcom\Codeception\Util\SystemHelper;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BundleTestCase extends TestCase
{
    protected function _after()
    {
        SystemHelper::cleanUp();

        parent::_after();
    }

    /**
     * @return ContainerInterface
     * @throws ModuleException
     */
    protected function getContainer()
    {
        return $this->getModule('\\' . PimcoreCore::class)->_getContainer();
    }

    /**
     * Asserts that a hierarchy of DOMElements matches.
     *
     * Backup implementation. Remove it after phpunit/pull/4507 has been merged
     * and replace it with assertDOMTreesEqualStructurally()
     *
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     *
     * @codeCoverageIgnore
     */
    public static function assertEqualXMLStructureByCodeception(\DOMElement $expectedElement, \DOMElement $actualElement, bool $checkAttributes = false, string $message = ''): void
    {
        $expectedElement = (new \DOMDocument)->importNode($expectedElement, true);
        $actualElement   = (new \DOMDocument)->importNode($actualElement, true);

        static::assertSame(
            $expectedElement->tagName,
            $actualElement->tagName,
            $message
        );

        if ($checkAttributes) {
            static::assertSame(
                $expectedElement->attributes->length,
                $actualElement->attributes->length,
                sprintf(
                    '%s%sNumber of attributes on node "%s" does not match',
                    $message,
                    !empty($message) ? "\n" : '',
                    $expectedElement->tagName
                )
            );

            for ($i = 0; $i < $expectedElement->attributes->length; $i++) {
                $expectedAttribute = $expectedElement->attributes->item($i);
                $actualAttribute   = $actualElement->attributes->getNamedItem($expectedAttribute->name);

                assert($expectedAttribute instanceof \DOMAttr);

                if (!$actualAttribute) {
                    static::fail(
                        sprintf(
                            '%s%sCould not find attribute "%s" on node "%s"',
                            $message,
                            !empty($message) ? "\n" : '',
                            $expectedAttribute->name,
                            $expectedElement->tagName
                        )
                    );
                }
            }
        }

        static::removeCharacterDataNodesByCodeception($expectedElement);
        static::removeCharacterDataNodesByCodeception($actualElement);

        static::assertSame(
            $expectedElement->childNodes->length,
            $actualElement->childNodes->length,
            sprintf(
                '%s%sNumber of child nodes of "%s" differs',
                $message,
                !empty($message) ? "\n" : '',
                $expectedElement->tagName
            )
        );

        for ($i = 0; $i < $expectedElement->childNodes->length; $i++) {
            static::assertEqualXMLStructureByCodeception(
                $expectedElement->childNodes->item($i),
                $actualElement->childNodes->item($i),
                $checkAttributes,
                $message
            );
        }
    }

    /**
     * Backup implementation. Remove it after phpunit/pull/4507 has been merged
     * and replace it with assertDOMTreesEqualStructurally()
     */
    public static function removeCharacterDataNodesByCodeception(\DOMNode $node): void
    {
        if ($node->hasChildNodes()) {
            for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
                if (($child = $node->childNodes->item($i)) instanceof \DOMCharacterData) {
                    $node->removeChild($child);
                }
            }
        }
    }

}
