<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use Exception;
use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\NotificationService;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

/**
 * Class ClearAppCachesTest
 *
 * @package OCA\Passwords\Migration
 */
class ClearAppCachesTest extends TestCase {

    /**
     * @var ClearAppCaches
     */
    protected $clearAppCaches;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FileCacheService
     */
    protected $fileCacheService;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void {
        $this->fileCacheService = $this->createMock(FileCacheService::class);
        $this->clearAppCaches     = new ClearAppCaches($this->fileCacheService);
    }

    /**
     *
     */
    public function testGetName(): void {
        $this->assertEquals('Clear app caches', $this->clearAppCaches->getName());
    }

    /**
     *
     */
    public function testClearDefaultCache() {
        $this->fileCacheService->expects($this->at(0))->method('clearCache')->with(FileCacheService::DEFAULT_CACHE);

        try {
            $this->clearAppCaches->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testClearAvatarCache() {
        $this->fileCacheService->expects($this->at(1))->method('clearCache')->with(FileCacheService::AVATAR_CACHE);

        try {
            $this->clearAppCaches->run(new IOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}