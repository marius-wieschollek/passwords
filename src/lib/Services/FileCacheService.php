<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;

/**
 * Class FileCacheService
 *
 * @package OCA\Passwords\Services
 */
class FileCacheService {

    const DEFAULT_CACHE   = 'default';
    const AVATAR_CACHE    = 'avatars';
    const FAVICON_CACHE   = 'favicon';
    const PREVIEW_CACHE   = 'preview';
    const PASSWORDS_CACHE = 'passwords';

    /**
     * @var IAppData
     */
    protected $appData;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var string
     */
    protected $defaultCache = self::DEFAULT_CACHE;

    /**
     * FileCacheService constructor.
     *
     * @param IAppData       $appData
     * @param LoggingService $logger
     */
    public function __construct(IAppData $appData, LoggingService $logger) {
        $this->appData = $appData;
        $this->logger  = $logger;
    }

    /**
     * @param string $cache
     *
     * @return ISimpleFolder
     * @throws NotPermittedException
     * @throws \Exception
     */
    public function getCache(string $cache = null): ISimpleFolder {
        $cache = $this->validateCacheName($cache);

        try {
            return $this->appData->getFolder($cache.'Cache');
        } catch(NotFoundException $e) {
            return $this->appData->newFolder($cache.'Cache');
        }
    }

    /**
     * @param null $cache
     *
     * @return array
     * @throws \Exception
     */
    public function getCacheInfo($cache = null): array {
        $cache = $this->validateCacheName($cache);
        $info  = [
            'name'  => $cache,
            'size'  => 0,
            'files' => 0
        ];

        try {
            $fileCache   = $this->getCache($cache);
            $cachedFiles = $fileCache->getDirectoryListing();
        } catch(NotPermittedException $e) {
            $this->logger->logException($e);

            return $info;
        }

        foreach($cachedFiles as $file) {
            $info['size'] += $file->getSize();
            $info['files']++;
        }

        return $info;
    }

    /**
     * @return array
     */
    public function listCaches(): array {
        return [
            self::DEFAULT_CACHE,
            self::AVATAR_CACHE,
            self::FAVICON_CACHE,
            self::PREVIEW_CACHE,
            self::PASSWORDS_CACHE,
        ];
    }

    /**
     * @param string $cache
     *
     * @return bool
     */
    public function hasCache(string $cache): bool {
        return in_array($cache, $this->listCaches());
    }

    /**
     * @param string $cache
     *
     * @return string
     * @throws \Exception
     */
    protected function validateCacheName(string $cache = null): string {
        if($cache === null) return $this->defaultCache;
        if(!$this->hasCache($cache)) throw new \Exception('Unknown Cache '.$cache);

        return $cache;
    }

    /**
     * @param string $cache
     */
    public function clearCache(string $cache = null): void {
        try {
            $this->getCache($cache)->delete();
            $class    = new \ReflectionClass($this->appData);
            $property = $class->getProperty('folders');
            $property->setAccessible(true);
            $property->setValue($this->appData, new \OC\Cache\CappedMemoryCache());
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }
    }

    /**
     *
     */
    public function clearAllCaches() {
        $caches = $this->listCaches();
        foreach($caches as $cache) {
            $this->clearCache($cache);
        }
    }

    /**
     * @param string $cache
     *
     * @return bool
     */
    public function isCacheEmpty(string $cache = null): bool {
        try {
            $files = $this->getCache($cache)->getDirectoryListing();

            return count($files) === 0;
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return true;
    }

    /**
     * @param string $file
     * @param string $cache
     *
     * @return bool
     */
    public function hasFile(string $file, string $cache = null): bool {
        try {
            return $this->getCache($cache)->fileExists($file);
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return false;
    }

    /**
     * @param string $file
     * @param string $cache
     *
     * @return ISimpleFile
     */
    public function getFile(string $file, string $cache = null): ?ISimpleFile {
        try {
            $cache = $this->getCache($cache);

            if($cache->fileExists($file)) return $cache->getFile($file);
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return null;
    }

    /**
     * @param string $file
     * @param string $content
     * @param string $cache
     *
     * @return ISimpleFile|null
     */
    public function putFile(string $file, string $content, string $cache = null): ?ISimpleFile {
        try {
            $cache = $this->getCache($cache);

            if($cache->fileExists($file)) {
                $fileModel = $cache->getFile($file);
            } else {
                $fileModel = $cache->newFile($file);
            }

            $fileModel->putContent($content);

            return $fileModel;
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return null;
    }

    /**
     * @param string $file
     * @param string $cache
     */
    public function removeFile(string $file, string $cache = null): void {
        try {
            $cache = $this->getCache($cache);
            if($cache->fileExists($file)) {
                $cache->getFile($file)->delete();
            }
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * @param string $cache
     *
     * @return FileCacheService
     */
    public function getCacheService(string $cache = self::DEFAULT_CACHE): FileCacheService {
        if(!$this->hasCache($cache)) $cache = self::DEFAULT_CACHE;

        $service = clone $this;
        $service->setDefaultCache($cache);

        return $service;
    }

    /**
     * @param string $defaultCache
     */
    protected function setDefaultCache(string $defaultCache = self::DEFAULT_CACHE) {
        $this->defaultCache = $defaultCache;
    }
}