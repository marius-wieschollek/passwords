<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Migration;

use Exception;
use OCA\Passwords\Services\FileCacheService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class ClearAppCaches
 *
 * @package OCA\Passwords\Migration
 */
class ClearAppCaches implements IRepairStep {

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCache;

    /**
     * ClearAppCaches constructor.
     *
     * @param FileCacheService $fileCache
     */
    public function __construct(FileCacheService $fileCache) {
        $this->fileCache = $fileCache;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Clear app caches';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        $this->fileCache->clearCache(FileCacheService::DEFAULT_CACHE);
        $this->fileCache->clearCache(FileCacheService::AVATAR_CACHE);

        $faviconCache = $this->fileCache->getCache(FileCacheService::FAVICON_CACHE);
        foreach($faviconCache->getDirectoryListing() as $file) {
            if($file->getSize() === 0) {
                $file->delete();
            }
        }
    }
}