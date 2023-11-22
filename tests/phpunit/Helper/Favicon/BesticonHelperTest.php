<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use Exception;
use OC\Files\SimpleFS\SimpleFile;
use OC\User\User;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\Time\DateTimeHelper;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class BesticonHelperTest
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class BesticonHelperTest extends TestCase {

    /**
     * @var BestIconHelper
     */
    private $besticonHelper;

    /**
     * @var MockObject|ImagickHelper
     */
    private $imageHelper;

    /**
     * @var MockObject|AdminUserHelper
     */
    private $adminService;

    /**
     * @var MockObject|DateTimeHelper
     */
    private $dateTimeHelper;

    /**
     * @var MockObject|FileCacheService
     */
    private $fileCacheService;

    /**
     * @var MockObject|IClientService
     */
    private $httpClientService;

    /**
     * @var MockObject|NotificationService
     */
    private $notificationService;

    /**
     * @var MockObject|ConfigurationService
     */
    private $configurationService;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     *
     */
    protected function setUp(): void {
        $this->imageHelper          = $this->createMock(ImagickHelper::class);
        $helperService              = $this->createMock(HelperService::class);
        $this->httpClientService    = $this->createMock(IClientService::class);
        $this->dateTimeHelper       = $this->createMock(DateTimeHelper::class);
        $this->adminService         = $this->createMock(AdminUserHelper::class);
        $this->fileCacheService     = $this->createMock(FileCacheService::class);
        $this->notificationService  = $this->createMock(NotificationService::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
        $this->configurationService = $this->createMock(ConfigurationService::class);
        $helperService->method('getImageHelper')->willReturn($this->imageHelper);
        $this->fileCacheService->method('getCacheService')->willReturn($this->fileCacheService);
        $this->besticonHelper = new BestIconHelper(
            $this->dateTimeHelper,
            $this->configurationService,
            $this->logger,
            $helperService,
            $this->adminService,
            $this->httpClientService,
            $this->fileCacheService,
            $this->notificationService
        );
    }

    /**
     *
     */
    public function testFaviconFilename() {
        $fileName = $this->besticonHelper->getFaviconFilename('www.example.com');

        $this->assertEquals('bi_www.example.com.png', $fileName);
    }

    /**
     *
     */
    public function testFaviconFilenameWithSize() {
        $fileName = $this->besticonHelper->getFaviconFilename('www.example.com', 192);

        $this->assertEquals('bi_www.example.com_192.png', $fileName);
    }

    /**
     * @throws Exception
     */
    public function testCachedFaviconReturned() {
        $fileName = $this->besticonHelper->getFaviconFilename('www.example.com');
        $file     = new SimpleFile();

        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(true);
        $this->fileCacheService->method('getFile')->with($fileName)->willReturn($file);

        $result = $this->besticonHelper->getFavicon('www.example.com');
        $this->assertEquals($file, $result);
    }

    /**
     * @throws Exception
     */
    public function testCallsWithCustomApiUrl() {
        $serviceUrl    = 'https://www.example.com/icon';
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $apiRequestUrl = "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url=https://{$domain}&formats=png,ico,gif,jpg";
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->with($this->besticonHelper::BESTICON_CONFIG_KEY, '')->willReturn($serviceUrl);
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $client->expects($this->once())->method('get')->with($apiRequestUrl);

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @throws Exception
     */
    public function testCallsWithSharedApi() {
        $serviceUrl    = $this->besticonHelper::BESTICON_SHARED_INSTANCE;
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $apiRequestUrl = "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url=https://{$domain}&formats=png,ico,gif,jpg";
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->dateTimeHelper->method('getInternationalWeek')->willReturn(0);
        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                [$this->besticonHelper::BESTICON_CONFIG_KEY, '', Application::APP_NAME, ''],
                [$this->besticonHelper::BESTICON_COUNTER_KEY, '0:0:0', Application::APP_NAME, '0:0:0'],
            ]
        );
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $client->expects($this->once())->method('get')->with($apiRequestUrl);

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @throws Exception
     */
    public function testWeekAndCounterAreSet() {
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->dateTimeHelper->method('getInternationalWeek')->willReturn(10);
        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                [$this->besticonHelper::BESTICON_CONFIG_KEY, '', Application::APP_NAME, ''],
                [$this->besticonHelper::BESTICON_COUNTER_KEY, '0:0:0', Application::APP_NAME, '10:5:0'],
            ]
        );
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $this->configurationService->expects($this->once())->method('setAppValue')->with($this->besticonHelper::BESTICON_COUNTER_KEY, '10:6:0');

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @throws Exception
     */
    public function testWeekAndCounterAreReset() {
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->dateTimeHelper->method('getInternationalWeek')->willReturn(10);
        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                [$this->besticonHelper::BESTICON_CONFIG_KEY, '', Application::APP_NAME, ''],
                [$this->besticonHelper::BESTICON_COUNTER_KEY, '0:0:0', Application::APP_NAME, '0:0:0'],
            ]
        );
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $this->configurationService->expects($this->once())->method('setAppValue')->with($this->besticonHelper::BESTICON_COUNTER_KEY, '10:1:0');

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @throws Exception
     */
    public function testNotificationSentIfLimitReached() {
        $fallbackColor = '#f0f0f0';
        $domain        = 'www.example.com';
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $limit         = $this->besticonHelper::BESTICON_INSTANCE_LIMIT;
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->dateTimeHelper->method('getInternationalWeek')->willReturn(10);
        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn($fallbackColor);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                [$this->besticonHelper::BESTICON_CONFIG_KEY, '', Application::APP_NAME, ''],
                [$this->besticonHelper::BESTICON_COUNTER_KEY, '0:0:0', Application::APP_NAME, '10:'.($limit - 1).':0'],
            ]
        );
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $user = $this->createMock(User::class);
        $user->method('getUID')->willReturn('admin');
        $this->adminService->method('getAdmins')->willReturn([$user]);

        $this->configurationService->expects($this->once())->method('setAppValue')->with($this->besticonHelper::BESTICON_COUNTER_KEY, '10:'.$limit.':1');
        $this->notificationService->expects($this->once())->method('sendBesticonApiNotification')->with('admin');

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @throws Exception
     */
    public function testNoNotificationSentIfLimitReachedAndNotified() {
        $fallbackColor = '#f0f0f0';
        $domain        = 'www.example.com';
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $limit         = $this->besticonHelper::BESTICON_INSTANCE_LIMIT;
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->dateTimeHelper->method('getInternationalWeek')->willReturn(10);
        $this->dateTimeHelper->method('getInternationalHour')->willReturn(12);
        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn($fallbackColor);
        $this->configurationService->method('getAppValue')->willReturnMap(
            [
                [$this->besticonHelper::BESTICON_CONFIG_KEY, '', Application::APP_NAME, ''],
                [$this->besticonHelper::BESTICON_COUNTER_KEY, '0:0:0', Application::APP_NAME, "10:{$limit}:1"],
            ]
        );
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $user = $this->createMock(User::class);
        $user->method('getUID')->willReturn('admin');
        $this->adminService->method('getAdmins')->willReturn([$user]);

        $this->configurationService->expects($this->once())->method('setAppValue')->with($this->besticonHelper::BESTICON_COUNTER_KEY, '10:'.($limit + 1).':1');
        $this->notificationService->expects($this->never())->method('sendBesticonApiNotification');

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @throws Exception
     */
    public function testReturnsApiImageData() {
        $serviceUrl    = 'https://www.example.com/icon';
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->with($this->besticonHelper::BESTICON_CONFIG_KEY, '')->willReturn($serviceUrl);
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $this->fileCacheService->expects($this->once())->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $result = $this->besticonHelper->getFavicon($domain);
        $this->assertEquals($file, $result);
    }

    /**
     * @throws Exception
     */
    public function testAttemptsHttpRequestIfHttpsFails() {
        $serviceUrl     = 'https://www.example.com/icon';
        $fallbackColor  = 'f0f0f0';
        $domain         = 'www.example.com';
        $apiRequestUrl1 = "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url=https://{$domain}&formats=png,ico,gif,jpg";
        $apiRequestUrl2 = "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url=http://{$domain}&formats=png,ico,gif,jpg";
        $faviconData    = 'data';
        $fileName       = $this->besticonHelper->getFaviconFilename($domain);
        $file           = new SimpleFile();

        $client  = $this->getHttpClientMock($faviconData, 500);
        $client2 = $this->getHttpClientMock();

        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->with($this->besticonHelper::BESTICON_CONFIG_KEY, '')->willReturn($serviceUrl);
        $this->httpClientService->method('newClient')->willReturnOnConsecutiveCalls($client, $client2);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $client->expects($this->exactly(1))->method('get')->with($apiRequestUrl1);
        $client2->expects($this->exactly(1))->method('get')->with($apiRequestUrl2);

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @throws Exception
     */
    public function testThrowsExceptionWhenNoData() {
        $serviceUrl    = 'https://www.example.com/icon';
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);

        $client = $this->getHttpClientMock('');
        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->with($this->besticonHelper::BESTICON_CONFIG_KEY, '')->willReturn($serviceUrl);
        $this->httpClientService->method('newClient')->willReturn($client);

        try {
            $this->besticonHelper->getFavicon($domain);
            $this->fail("Expected exception");
        } catch(Exception $e) {
            $this->assertEquals('Favicon service returned no data', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testThrowsExceptionWhenUnsupportedType() {
        $serviceUrl    = 'https://www.example.com/icon';
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $client        = $this->getHttpClientMock();

        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->with($this->besticonHelper::BESTICON_CONFIG_KEY, '')->willReturn($serviceUrl);
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(false);
        $this->imageHelper->method('getImageMime')->with($faviconData)->willReturn('text/plain');

        try {
            $this->besticonHelper->getFavicon($domain);
            $this->fail("Expected exception");
        } catch(Exception $e) {
            $this->assertEquals('Favicon service returned unsupported data type: text/plain', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testImageTypeValidated() {
        $serviceUrl    = 'https://www.example.com/icon';
        $fallbackColor = 'f0f0f0';
        $domain        = 'www.example.com';
        $faviconData   = 'data';
        $fileName      = $this->besticonHelper->getFaviconFilename($domain);
        $file          = new SimpleFile();
        $client        = $this->getHttpClientMock();

        $this->fileCacheService->method('hasFile')->with($fileName)->willReturn(false);
        $this->fileCacheService->method('putFile')->with($fileName, $faviconData)->willReturn($file);
        $this->fallbackIconGenerator->method('stringToColor')->with($domain)->willReturn('#'.$fallbackColor);
        $this->configurationService->method('getAppValue')->with($this->besticonHelper::BESTICON_CONFIG_KEY, '')->willReturn($serviceUrl);
        $this->httpClientService->method('newClient')->willReturn($client);
        $this->imageHelper->method('supportsImage')->with($faviconData)->willReturn(true);

        $this->imageHelper->expects($this->once())->method('supportsImage')->with($faviconData);

        $this->besticonHelper->getFavicon($domain);
    }

    /**
     * @param string $faviconData
     * @param int    $status
     *
     * @return IClient
     * @throws \ReflectionException
     */
    protected function getHttpClientMock($faviconData = 'data', $status = 200) {
        /** @var IResponse|MockObject $response */
        $response = $this->createMock(IResponse::class);
        $response->method('getBody')->willReturn($faviconData);
        $response->method('getStatusCode')->willReturn($status);

        /** @var IClient|MockObject $client */
        $client = $this->createMock(IClient::class);
        $client->method('get')->willReturn($response);

        return $client;
    }
}