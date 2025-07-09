<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use Exception;
use OC\Files\SimpleFS\SimpleFile;
use OC\Files\SimpleFS\SimpleFolder;
use OC\Migration\SimpleOutput;
use OCA\Passwords\Services\FileCacheService;
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
        $matcher = $this->exactly(2);

        $this->fileCacheService->expects($matcher)
                               ->method('clearCache')
                               ->willReturnCallback(function (string $param) use ($matcher) {
                                   match ($matcher->numberOfInvocations()) {
                                       1 => $this->assertEquals($param, FileCacheService::DEFAULT_CACHE),
                                       2 => $this->assertEquals($param, FileCacheService::AVATAR_CACHE),
                                   };

                                   return true;
                               });

        $this->fileCacheService->method('getCache')->willReturn(new SimpleFolder());

        try {
            $this->clearAppCaches->run(new SimpleOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     *
     */
    public function testDeletesBrokenFavicons() {
        $this->fileCacheService->method('clearCache')->willReturn(true);

        $goodFavicon = $this->createMock(SimpleFile::class);
        $goodFavicon->expects($this->once())->method('getSize')->willReturn(123);
        $goodFavicon->expects($this->never())->method('delete');

        $badFavicon = $this->createMock(SimpleFile::class);
        $badFavicon->expects($this->once())->method('getSize')->willReturn(0);
        $badFavicon->expects($this->once())->method('delete');

        $folder = $this->createMock(SimpleFolder::class);
        $folder->expects($this->once())
               ->method('getDirectoryListing')
               ->willReturn([$goodFavicon, $badFavicon]);

        $this->fileCacheService->method('getCache')->willReturn($folder);

        try {
            $this->clearAppCaches->run(new SimpleOutput());
        } catch(Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}