<?php
/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services;

use OC\Lock\NoopLockingProvider;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

class LockService {

    /**
     * @var array
     */
    protected array $fallbackLocks = [];

    public function __construct(
        protected ConfigurationService $config,
        protected ILockingProvider     $lockingProvider,
        protected ITimeFactory        $timeFactory,
    ) {
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isLocked(string $path): bool {
        if($this->isLockingEnabled()) {
            return $this->lockingProvider->isLocked($path, ILockingProvider::LOCK_EXCLUSIVE);
        }

        $this->config->clearCache();
        $lock = $this->config->getAppValueInt(
            $this->getFallbackLockKey($path)
        );

        return $lock !== 0 && $lock > $this->timeFactory->getDateTime('-1 hour')->getTimestamp();
    }

    /**
     * @param string $path
     *
     * @return void
     * @throws LockedException
     */
    public function acquireLock(string $path): void {
        if($this->isLockingEnabled()) {
            $this->lockingProvider->acquireLock($path, ILockingProvider::LOCK_EXCLUSIVE);

            return;
        }

        if($this->isLocked($path)) {
            throw new LockedException($path, null, null, null);
        }
        $this->config->setAppValueInt(
            $this->getFallbackLockKey($path),
            $this->timeFactory->getTime()
        );
        $this->fallbackLocks[] = $path;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function releaseLock(string $path): void {
        if($this->isLockingEnabled()) {
            $this->lockingProvider->releaseLock($path, ILockingProvider::LOCK_EXCLUSIVE);

            return;
        }

        $this->config->deleteAppValue(
            $this->getFallbackLockKey($path)
        );
    }

    /**
     * @return void
     */
    public function releaseAll(): void {
        if($this->isLockingEnabled()) {
            $this->lockingProvider->releaseAll();

            return;
        }

        foreach($this->fallbackLocks as $lock) {
            $this->releaseLock($lock);
        }
        $this->fallbackLocks = [];
    }

    /**
     * @return bool
     */
    protected function isLockingEnabled(): bool {
        return $this->config->getSystemValueBool('filelocking.enabled', true) && !($this->lockingProvider instanceof NoopLockingProvider);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getFallbackLockKey(string $path): string {
        return 'lock/'.sha1($path);
    }
}