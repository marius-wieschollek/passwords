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

namespace OCA\Passwords\Provider\SecurityCheck;

use OCA\Passwords\Helper\SecurityCheck\UserRulesSecurityCheck;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class HaveIBeenPwnedProviderTest
 *
 * @package OCA\Passwords\Provider\SecurityCheck
 */
class HaveIBeenPwnedProviderTest extends TestCase {

    /**
     * @var LoggingService|MockObject
     */
    protected LoggingService|MockObject $logger;

    /**
     * @var IClientService|MockObject
     */
    protected IClientService|MockObject $httpClientService;

    /**
     * @var FileCacheService|MockObject
     */
    protected FileCacheService|MockObject $fileCacheService;

    /**
     * @var UserRulesSecurityCheck|MockObject
     */
    protected UserRulesSecurityCheck|MockObject $userRulesCheck;

    /**
     * @var ConfigurationService|MockObject
     */
    protected ConfigurationService|MockObject $config;

    protected function setUp(): void {
        $this->logger            = $this->createMock(LoggingService::class);
        $this->httpClientService = $this->createMock(IClientService::class);
        $this->fileCacheService  = $this->createMock(FileCacheService::class);
        $this->userRulesCheck    = $this->createMock(UserRulesSecurityCheck::class);
        $this->config            = $this->createMock(ConfigurationService::class);

        $this->fileCacheService->method('getCacheService')->willReturnSelf();
        $this->fileCacheService->method('hasFile')->willReturn(false);
        $this->config->method('getAppValue')->willReturnCallback(
            function(string $key, $default = null) {
                return $default;
            }
        );
    }

    /**
     * The HIBP api returns hundreds of hashes per range. The provider must not
     * keep all of them in memory when checking many different ranges.
     */
    public function testHashCacheDoesNotGrowUnbounded(): void {
        $this->mockHttpClient();
        $provider = $this->createProvider();

        for($i = 0; $i < 500; $i++) {
            $provider->isHashSecure(sha1('random-password-'.$i));
        }

        $rangeCache = (new \ReflectionProperty(HaveIBeenPwnedProvider::class, 'checkedRanges'))->getValue($provider)->getData();
        $hashCache  = (new \ReflectionProperty(AbstractSecurityCheckProvider::class, 'hashStatusCache'))->getValue($provider)->getData();

        $this->assertLessThanOrEqual(HaveIBeenPwnedProvider::RANGE_CACHE_SIZE, count($rangeCache));
        $this->assertLessThanOrEqual(AbstractSecurityCheckProvider::HASH_CACHE_SIZE, count($hashCache));
    }

    /**
     *
     */
    public function testCompromisedHashIsDetected(): void {
        $hash  = sha1('known-breached-password');
        $range = substr($hash, 0, 5);
        $this->mockHttpClient([$range => [$hash]]);
        $provider = $this->createProvider();

        $this->assertFalse($provider->isHashSecure($hash));
        $this->assertTrue($provider->isHashSecure(sha1('known-secure-password')));
    }

    /**
     * When the range cache evicts a range, the hashes of that range must be
     * fetched again instead of being reported as secure.
     */
    public function testCompromisedHashIsDetectedAfterRangeCacheEviction(): void {
        $range     = 'abcde';
        $hashOne   = $range.str_pad('1', 35, '0', STR_PAD_LEFT);
        $hashTwo   = $range.str_pad('2', 35, '0', STR_PAD_LEFT);
        $this->mockHttpClient([$range => [$hashOne, $hashTwo]]);
        $provider = $this->createProvider();

        $this->assertFalse($provider->isHashSecure($hashOne));

        for($i = 0; $i < HaveIBeenPwnedProvider::RANGE_CACHE_SIZE + 10; $i++) {
            $provider->isHashSecure(sha1('filler-password-'.$i));
        }

        $this->assertFalse($provider->isHashSecure($hashTwo));
    }

    /**
     * @param array $rangesWithHashes Map of range => list of full hashes in that range
     */
    protected function mockHttpClient(array $rangesWithHashes = []): void {
        $client = $this->createMock(IClient::class);
        $client->method('get')->willReturnCallback(
            function(string $uri) use ($rangesWithHashes) {
                $range = substr($uri, strrpos($uri, '/') + 1);
                $lines = [];
                if(isset($rangesWithHashes[ $range ])) {
                    foreach($rangesWithHashes[ $range ] as $hash) {
                        $lines[] = substr($hash, 5).':1';
                    }
                } else {
                    for($i = 0; $i < 500; $i++) {
                        $lines[] = str_pad(strtoupper(dechex(crc32($range.$i))), 35, '0', STR_PAD_LEFT).':1';
                    }
                }

                $response = $this->createMock(IResponse::class);
                $response->method('getBody')->willReturn(implode("\r\n", $lines)."\r\n");

                return $response;
            }
        );
        $this->httpClientService->method('newClient')->willReturn($client);
    }

    /**
     * @return HaveIBeenPwnedProvider
     */
    protected function createProvider(): HaveIBeenPwnedProvider {
        return new HaveIBeenPwnedProvider(
            $this->logger,
            $this->httpClientService,
            $this->fileCacheService,
            $this->userRulesCheck,
            $this->config
        );
    }
}
