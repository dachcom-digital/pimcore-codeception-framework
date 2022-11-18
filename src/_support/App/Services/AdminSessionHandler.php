<?php

namespace Dachcom\Codeception\App\Services;

use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @deprecated
 * @see https://github.com/pimcore/pimcore/issues/11927#issuecomment-1320510099
 */
class AdminSessionHandler extends \Pimcore\Bundle\AdminBundle\Session\Handler\AdminSessionHandler
{
    private $openedSessions = 0;
    private $canWriteAndClose;

    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;

        parent::__construct($requestHelper);
    }

     public function loadSession(): SessionInterface
    {
        if (!$this->getSession()->isStarted()) {
            $this->getSession()->start();
        }

        $this->openedSessions++;

        return $this->getSession();
    }

    public function writeClose()
    {
        if (!$this->shouldWriteAndClose()) {
            return;
        }

        $this->openedSessions--;

        if (0 === $this->openedSessions) {
            $this->getSession()->save();
        }
    }

    private function getSession()
    {
        try {
            return $this->requestHelper->getSession();
        } catch (\LogicException $e) {
            $this->logger->debug('Error while getting the admin session: {exception}', ['exception' => $e->getMessage()]);
        }

        return \Pimcore::getContainer()->get('session');
    }

    private function shouldWriteAndClose(): bool
    {
        try {
            $request = $this->requestHelper->getMainRequest();
        } catch (\Throwable $e) {
            return true;
        }

        return $this->canWriteAndClose ??= $this->isAdminRequest($request);
    }

    private function isAdminRequest(Request $request): bool
    {
        return $this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)
            || $this->requestHelper->isFrontendRequestByAdmin($request);
    }
}
