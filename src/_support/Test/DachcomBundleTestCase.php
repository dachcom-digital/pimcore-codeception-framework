<?php

namespace Dachcom\Codeception\Test;

use Codeception\Exception\ModuleException;
use Dachcom\Codeception\Helper\PimcoreCore;
use Dachcom\Codeception\Util\SystemHelper;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class DachcomBundleTestCase extends TestCase
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
        return $this->getPimcoreBundle()->getContainer();
    }

    /**
     * @return PimcoreCore
     * @throws ModuleException
     */
    protected function getPimcoreBundle()
    {
        return $this->getModule('\\' . PimcoreCore::class);
    }
}
