<?php

namespace Dachcom\Codeception\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Pimcore\Model\User;
use Pimcore\Tests\Util\TestHelper;
use Pimcore\Tool\Authentication;

class PimcoreUser extends Module
{
    protected array $users = [];

    public function _before(TestInterface $test)
    {
        parent::_before($test);
    }

    /**
     * Actor Function to create a User
     */
    public function haveAUser(string $username): User
    {
        $user = $this->createUser($username, false);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * Actor Function to create a Admin User
     */
    public function haveAUserWithAdminRights(string $username): User
    {
        $user = $this->createUser($username, true);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * API Function to get a User
     */
    public function getUser(string $username): User
    {
        if (isset($this->users[$username])) {
            return $this->users[$username];
        }

        throw new \InvalidArgumentException(sprintf('User %s does not exist', $username));
    }

    /**
     * API Function to create a User
     */
    protected function createUser(string $username, bool $admin = true): ?User
    {
        if (!TestHelper::supportsDbTests()) {
            $this->debug(sprintf('[PIMCORE USER MODULE] Not initializing user %s as DB is not connected', $username));
            return null;
        }

        $this->debug(sprintf('[PIMCORE USER MODULE] Initializing user %s', $username));

        $user = null;
        $password = $username;

        try {
            $user = User::getByName($username);
        } catch (\Exception $e) {
            // fail silently
        }

        if ($user instanceof User) {
            return $user;
        }

        $this->debug(sprintf('[PIMCORE USER MODULE] Creating user %s', $username));

        $pass = null;

        try {
            $pass = Authentication::getPasswordHash($username, $password);
        } catch (\Exception $e) {
            // fail silently.
        }

        $user = User::create([
            'parentId' => 0,
            'username' => $username,
            'password' => $pass,
            'active'   => true,
            'admin'    => $admin
        ]);

        $this->users[$user->getName()] = $user;

        return $user;
    }
}
