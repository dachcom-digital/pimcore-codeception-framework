<?php

namespace Dachcom\Codeception\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pimcore\Bundle\InstallBundle\Installer;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DbHelper
{
    public static function setupDb(Connection $connection, bool $initializeDb, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher)
    {
        if ($initializeDb === true) {
            // (re-)initialize DB
            $connected = self::initializeDb($connection, $logger, $eventDispatcher);
        } else {
            // just try to connect without initializing the DB
            self::connectDb($connection);
            $connected = true;
        }

        if ($connected) {
            !defined('PIMCORE_TEST_DB_INITIALIZED') && define('PIMCORE_TEST_DB_INITIALIZED', true);
        }
    }

    protected static function initializeDb(Connection $connection, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher): bool
    {
        $dbName = $connection->getParams()['dbname'];

        codecept_debug(sprintf('[DB] Initializing DB %s', $dbName));

        self::dropAndCreateDb($connection);
        self::connectDb($connection);

        $installer = new Installer($logger, $eventDispatcher);
        $installer->setImportDatabaseDataDump(false);
        $installer->setupDatabase([
            'username' => 'admin',
            'password' => microtime(),
        ]);

        codecept_debug(sprintf('[DB] Initialized the test DB %s', $dbName));

        return true;
    }

    protected static function dropAndCreateDb(Connection $connection): void
    {
        $dbName = self::getDbName($connection);
        $params = $connection->getParams();
        $config = $connection->getConfiguration();

        unset($params['url']);
        unset($params['dbname']);

        // use a dedicated setup connection as the framework connection is bound to the DB and will
        // fail if the DB doesn't exist
        $setupConnection = DriverManager::getConnection($params, $config);
        $schemaManager = $setupConnection->getSchemaManager();

        $databases = $schemaManager->listDatabases();

        if (in_array($dbName, $databases)) {
            codecept_debug(sprintf('[DB] Dropping DB %s', $dbName));
            $schemaManager->dropDatabase($connection->quoteIdentifier($dbName));
        }

        codecept_debug(sprintf('[DB] Creating DB %s', $dbName));

        $schemaManager->createDatabase($connection->quoteIdentifier($dbName) . ' charset=utf8mb4');
    }

    protected static function connectDb(Connection $connection): void
    {
        if (!$connection->isConnected()) {
            $connection->connect();
        }

        codecept_debug(sprintf('[DB] Successfully connected to DB %s', $connection->getDatabase()));
    }

    protected static function getDbName(Connection $connection): string
    {
        return $connection->getParams()['dbname'];
    }
}
