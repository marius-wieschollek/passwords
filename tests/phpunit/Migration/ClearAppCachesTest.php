<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use Exception;
use OC\Log;
use OC\Migration\SimpleOutput;
use OCA\Passwords\Services\FileCacheService;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var MockObject|FileCacheService
     */
    protected $fileCacheService;

    /**
     *
     */
    protected function setUp(): void {
        $this->fileCacheService = $this->createMock(FileCacheService::class);
        $this->clearAppCaches   = new ClearAppCaches($this->fileCacheService);
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
    public function testClearsAppCaches() {
        $this->fileCacheService->expects($this->exactly(2))
                               ->method('clearCache')
                               ->withConsecutive(
                                   [FileCacheService::DEFAULT_CACHE],
                                   [FileCacheService::AVATAR_CACHE]
                               );

        try {
            $this->clearAppCaches->run(new SimpleOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}