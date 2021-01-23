<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Settings\ServerSettingsHelper;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Class DeferredActivationServiceTest
 *
 * @package OCA\Passwords\Services
 */
class DeferredActivationServiceTest extends TestCase {

    /**
     * @var MockObject|LoggingService
     */
    protected $loggingService;

    /**
     * @var MockObject|ServerSettingsHelper
     */
    protected $settingsHelper;

    /**
     * @var MockObject|FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var MockObject|IClientService
     */
    protected $httpClientService;

    /**
     * @var MockObject|ConfigurationService
     */
    protected $configurationService;

    /**
     * @var DeferredActivationService
     */
    protected $deferredActivationService;

    /**
     *
     */
    protected function setUp(): void {
        $this->loggingService       = $this->createMock(LoggingService::class);
        $this->httpClientService    = $this->createMock(IClientService::class);
        $this->fileCacheService     = $this->createMock(FileCacheService::class);
        $this->settingsHelper       = $this->createMock(ServerSettingsHelper::class);
        $this->configurationService = $this->createMock(ConfigurationService::class);
        $this->fileCacheService->method('getCacheService')->willReturn($this->fileCacheService);
        $this->deferredActivationService = new DeferredActivationService(
            $this->loggingService,
            $this->fileCacheService,
            $this->configurationService,
            $this->httpClientService,
            $this->settingsHelper
        );
    }

    /**
     *
     */
    public function testGetDasValue() {
        $this->configurationService->method('getSystemValue')->willReturn(true);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                ['das/enabled', '1', Application::APP_NAME, '1'],
                ['installed_version', null, Application::APP_NAME, '2020.4.0'],
            ]
        );

        $fakeFile = $this->getFileMock();
        $fakeFile->method('getMTime')->willReturn(time());
        $fakeFile->method('getContent')->willReturn('{"server":{"2020.4.0":{"test":true}}}');
        $this->fileCacheService->method('getFile')->willReturn($fakeFile);

        $this->assertEquals(true, $this->deferredActivationService->check('test'));
    }

    /**
     * @throws ReflectionException
     */
    public function testLocalStorageIsCheckedBeforeFetchingRemote() {
        $this->configurationService->method('getSystemValue')->willReturn(true);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                ['das/enabled', '1', Application::APP_NAME, '1'],
                ['installed_version', null, Application::APP_NAME, '2020.4.0'],
            ]
        );

        $fakeFile = $this->getFileMock();
        $fakeFile->method('getMTime')->willReturn(0);

        $this->fileCacheService->method('getFile')->willReturn($fakeFile);
        $this->fileCacheService->expects($this->once())->method('getFile')->with('features.json');
        $fakeFile->expects($this->once())->method('getMTime');

        $this->deferredActivationService->check('test');
    }

    /**
     *
     */
    public function testFetchResultFromRemoteIfMissing() {
        $this->configurationService->method('getSystemValue')->willReturn(true);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                ['das/enabled', '1', Application::APP_NAME, '1'],
                ['installed_version', null, Application::APP_NAME, '2020.4.0'],
            ]
        );

        /** @var IResponse|MockObject $response */
        $response = $this->createMock(IResponse::class);
        $response->method('getBody')->willReturn('{}');
        $response->method('getStatusCode')->willReturn(200);

        /** @var IClient|MockObject $client */
        $client = $this->createMock(IClient::class);
        $client->method('get')->willReturn($response);

        $this->httpClientService->method('newClient')->willReturn($client);
        $client->expects($this->once())->method('get')->with('https://example.com/_features/features-v1.json');

        $this->fileCacheService->method('getFile')->willReturn(null);
        $this->settingsHelper->method('get')->with('handbook.url')->willReturn('https://example.com/');

        $this->deferredActivationService->check('test');
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchResultIfOutdated() {
        $this->configurationService->method('getSystemValue')->willReturn(true);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                ['das/enabled', '1', Application::APP_NAME, '1'],
                ['installed_version', null, Application::APP_NAME, '2020.4.0'],
            ]
        );

        $fakeFile = $this->getFileMock();
        $fakeFile->method('getMTime')->willReturn(0);
        $this->settingsHelper->method('get')->with('handbook.url')->willReturn('https://example.com/');

        $fakeFile->expects($this->once())->method('getMTime');
        $this->fileCacheService->expects($this->once())->method('getFile')->with('features.json');

        /** @var IResponse|MockObject $response */
        $response = $this->createMock(IResponse::class);
        $response->method('getBody')->willReturn('{}');
        $response->method('getStatusCode')->willReturn(200);

        /** @var IClient|MockObject $client */
        $client = $this->createMock(IClient::class);
        $client->method('get')->willReturn($response);

        $this->httpClientService->method('newClient')->willReturn($client);
        $client->expects($this->once())->method('get')->with('https://example.com/_features/features-v1.json');

        $this->deferredActivationService->check('test');
    }

    /**
     *
     */
    public function testDefaultEnabledInNightly() {
        $this->configurationService->method('getSystemValue')->willReturn(true);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                ['das/enabled', '1', Application::APP_NAME, '1'],
                ['installed_version', null, Application::APP_NAME, '2020.4.0-build1234'],
            ]
        );

        $this->assertEquals(true, $this->deferredActivationService->check('test'));
    }

    /**
     *
     */
    public function testForceCheckInNightly() {
        $this->configurationService->method('getSystemValue')->willReturn(true);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                ['das/enabled', '1', Application::APP_NAME, '1'],
                ['installed_version', null, Application::APP_NAME, '2020.4.0-build1234'],
            ]
        );

        $fakeFile = $this->getFileMock();
        $fakeFile->method('getMTime')->willReturn(time());
        $fakeFile->method('getContent')->willReturn('{"server":{"2020.4.0":{"test":false}}}');
        $this->fileCacheService->method('getFile')->willReturn($fakeFile);

        $this->assertEquals(false, $this->deferredActivationService->check('test', true));
    }

    /**
     *
     */
    public function testDasDisableSetting() {
        $this->configurationService->expects($this->never())->method('getSystemValue');
        $this->configurationService->expects($this->exactly(1))->method('getAppValue')->with('das/enabled', '1', Application::APP_NAME)->willReturn('0');

        $this->assertEquals(false, $this->deferredActivationService->check('test'));
    }

    /**
     *
     */
    public function testServerOfflineFlag() {
        $this->configurationService->expects($this->once())->method('getAppValue')->with('das/enabled', '1', Application::APP_NAME)->willReturn('1');
        $this->configurationService->expects($this->once())->method('getSystemValue')->with('has_internet_connection', true)->willReturn(false);

        $this->assertEquals(false, $this->deferredActivationService->check('test'));
    }

    /**
     * @return MockObject
     * @throws ReflectionException
     */
    protected function getFileMock(): MockObject {
        $fakeFile = $this
            ->getMockBuilder(ISimpleFile::class)
            ->setMethods(['getMTime', 'getContent'])
            ->getMock();
        $this->fileCacheService->method('getFile')->willReturn($fakeFile);

        return $fakeFile;
    }
}