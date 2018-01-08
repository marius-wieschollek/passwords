<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 04.09.17
 * Time: 20:31
 */

namespace OCA\Passwords\Services;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
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
    const PAGESHOT_CACHE  = 'pageshot';
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
     * @throws \Exception
     * @throws \OCP\Files\NotPermittedException
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
     * @throws \OCP\Files\NotPermittedException
     */
    public function getCacheInfo($cache = null): array {
        $cache = $this->validateCacheName($cache);

        $fileCache   = $this->getCache($cache);
        $cachedFiles = $fileCache->getDirectoryListing();

        $info = [
            'name'  => $cache,
            'size'  => 0,
            'files' => 0
        ];

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
            self::PAGESHOT_CACHE,
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
        if($cache === null) {
            return $this->defaultCache;
        }
        if(!$this->hasCache($cache)) throw new \Exception('Unknown Cache '.$cache);

        return $cache;
    }

    /**
     * @param string $cache
     */
    public function clearCache(string $cache = null): void {
        try {
            $cache = $this->validateCacheName($cache);
            $this->getCache($cache)->delete();
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
            $info = $this->getCacheInfo($cache);

            return $info['files'] == 0;
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
            $cache = $this->validateCacheName($cache);

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
            $cache = $this->validateCacheName($cache);
            $cache = $this->getCache($cache);

            if($cache->fileExists($file)) {
                return $cache->getFile($file);
            }
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
            $cache = $this->validateCacheName($cache);
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
            $cache = $this->validateCacheName($cache);

            $this->getFile($file, $cache)->delete();
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * @param string $defaultCache
     */
    public function setDefaultCache(string $defaultCache = self::DEFAULT_CACHE) {
        $this->defaultCache = $defaultCache;
    }
}