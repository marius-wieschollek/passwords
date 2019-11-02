<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

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
    protected $fileCache;

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
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        $this->fileCache->clearCache(FileCacheService::DEFAULT_CACHE);
        $this->fileCache->clearCache(FileCacheService::AVATAR_CACHE);
    }
}