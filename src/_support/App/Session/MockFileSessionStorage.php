<?php

namespace Dachcom\Codeception\App\Session;

use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Overwritten until https://github.com/symfony/symfony/pull/39167 has been resolved!
 */
class MockFileSessionStorage extends MockArraySessionStorage
{
    private $savePath;

    /**
     * @param string|null      $savePath Path of directory to save session files
     * @param string           $name     Session name
     * @param MetadataBag|null $metaBag
     */
    public function __construct(string $savePath = null, string $name = 'MOCKSESSID', MetadataBag $metaBag = null)
    {
        if (null === $savePath) {
            $savePath = sys_get_temp_dir();
        }

        if (!is_dir($savePath) && !@mkdir($savePath, 0777, true) && !is_dir($savePath)) {
            throw new \RuntimeException(sprintf('Session Storage was not able to create directory "%s".', $savePath));
        }

        $this->savePath = $savePath;

        parent::__construct($name, $metaBag);
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if ($this->started) {
            return true;
        }

        if (!$this->id) {
            $this->id = $this->generateId();
        }

        $this->read();

        $this->started = true;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->started) {
            $this->start();
        }

        if ($destroy) {
            $this->destroy();
        }

        return parent::regenerate($destroy, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        if (!$this->started) {
            throw new \RuntimeException('Trying to save a session that was not started yet or was already closed.');
        }

        $data = $this->data;

        foreach ($this->bags as $bag) {
            if (empty($data[$key = $bag->getStorageKey()])) {
                unset($data[$key]);
            }
        }
        if ([$key = $this->metadataBag->getStorageKey()] === array_keys($data)) {
            unset($data[$key]);
        }

        try {
            if ($data) {
                $path = $this->getFilePath();
                $tmp = $path.bin2hex(random_bytes(6));
                file_put_contents($tmp, serialize($data));
                rename($tmp, $path);
            } else {
                $this->destroy();
            }
        } finally {
            $this->data = $data;
        }

        // this is needed for Silex, where the session object is re-used across requests
        // in functional tests. In Symfony, the container is rebooted, so we don't have
        // this issue
        $this->started = false;
    }

    /**
     * Deletes a session from persistent storage.
     * Deliberately leaves session data in memory intact.
     */
    private function destroy(): void
    {
        set_error_handler(static function () {});
        try {
            unlink($this->getFilePath());
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Calculate path to file.
     */
    private function getFilePath(): string
    {
        return $this->savePath . '/' . $this->id . '.mocksess';
    }

    /**
     * Reads session from storage and loads session.
     */
    private function read(): void
    {
        $filePath = $this->getFilePath();
        $this->data = is_readable($filePath) && is_file($filePath) ? unserialize(file_get_contents($filePath)) : [];

        $this->loadSession();
    }
}