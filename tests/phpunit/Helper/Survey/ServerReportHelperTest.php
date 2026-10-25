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

namespace OCA\Passwords\Helper\Survey;

use Exception;
use OC_App;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Helper\Image\AutoImageHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;
use OCP\Http\Client\IClientService;
use OCP\ServerVersion;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Validates every report produced by ServerReportHelper::getReport against
 * tests/phpunit/Fixtures/report.schema.json, which mirrors what the statistics
 * server accepts.
 */
class ServerReportHelperTest extends TestCase {

    const string SCHEMA_FILE = __DIR__.'/../../Fixtures/report.schema.json';

    protected array  $appValues;
    protected array  $appIntValues;
    protected array  $systemValues;
    protected array  $serverVersion;
    protected string $channel;
    protected int    $shareCount;
    protected string $realImageHelper;
    protected bool   $imageHelperFails;
    protected array  $serviceSettings;
    protected bool   $serviceSettingsFail;
    /** @var array<string, array<string, int>> field => value => count, split across the three mappers */
    protected array $revisionCounts;
    protected bool  $revisionMappersFail;

    protected function setUp(): void {
        $this->appValues           = [
            'installed_version'       => '2026.10.0',
            'web/php/version/id'  => '80412',
            'cron/php/version/id' => '80412',
        ];
        $this->appIntValues        = ['auto-backup/status' => 0];
        $this->systemValues        = ['dbtype' => 'mysql'];
        $this->serverVersion       = [32, 0, 1, 2];
        $this->channel             = 'stable';
        $this->shareCount          = 0;
        $this->realImageHelper     = HelperService::IMAGES_IMAGICK;
        $this->imageHelperFails    = false;
        $this->serviceSettings     = [
            'preview.api' => ['value' => '', 'default' => ''],
            'favicon.api' => ['value' => 'https://icons.example', 'default' => 'https://icons.example'],
        ];
        $this->serviceSettingsFail = false;
        $this->revisionCounts      = [
            'sse_type' => ['SSEv1r2' => 3],
            'cse_type' => ['none' => 3],
        ];
        $this->revisionMappersFail = false;

        OC_App::$apps = [];
    }

    protected function tearDown(): void {
        OC_App::$apps = [];
    }

    public function testMinimalReportMatchesSchema(): void {
        $report = $this->getReport(false);

        $this->assertSame(['version', 'environment'], array_keys($report));
        $this->assertReportMatchesSchema($report);
    }

    public function testEnhancedReportMatchesSchema(): void {
        $report = $this->getReport();

        $this->assertSame(
            ['version', 'environment', 'services', 'settings', 'status', 'apps', 'sharing', 'encryption'],
            array_keys($report)
        );
        $this->assertReportMatchesSchema($report);
    }

    public function testReportWithMissingOptionalConfigMatchesSchema(): void {
        unset($this->appValues['web/php/version/id'], $this->appValues['cron/php/version/id']);
        $this->appIntValues = [];

        $this->assertReportMatchesSchema($this->getReport());
    }

    #[DataProvider('appVersionProvider')]
    public function testAppVersionMatchesSchema(string $version): void {
        $this->appValues['installed_version'] = $version;

        $this->assertReportMatchesSchema($this->getReport(false));
    }

    public static function appVersionProvider(): array {
        return [
            'release' => ['2026.10.0'],
            'patch'   => ['2026.1.123'],
            'nightly' => ['2026.10.0-build12345'],
        ];
    }

    #[DataProvider('serverVersionProvider')]
    public function testServerVersionMatchesSchema(array $version): void {
        $this->serverVersion = $version;

        $this->assertReportMatchesSchema($this->getReport(false));
    }

    public static function serverVersionProvider(): array {
        return [
            'zeros'      => [[32, 0, 0, 0]],
            'patch'      => [[32, 0, 1, 2]],
            'two digits' => [[33, 10, 12, 99]],
        ];
    }

    #[DataProvider('phpVersionProvider')]
    public function testPhpVersionMatchesSchema(?string $versionId, string $expected): void {
        $this->appValues['web/php/version/id']  = $versionId;
        $this->appValues['cron/php/version/id'] = $versionId;

        $report = $this->getReport(false);

        $this->assertSame($expected, $report['version']['php']);
        $this->assertSame($expected, $report['version']['cronPhp']);
        $this->assertReportMatchesSchema($report);
    }

    public static function phpVersionProvider(): array {
        $runtime = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;

        return [
            'runtime'           => [strval(PHP_VERSION_ID), $runtime],
            'release'           => ['80412', '8.4.12'],
            'release candidate' => ['80500', '8.5.0'],
            'php 7'             => ['70433', '7.4.33'],
            'php 10'            => ['101102', '10.11.2'],
            'missing'           => [null, $runtime],
            'invalid'           => ['unknown', $runtime],
        ];
    }

    #[DataProvider('databaseProvider')]
    public function testDatabaseMatchesSchema(string $database): void {
        $this->systemValues['dbtype'] = $database;

        $this->assertReportMatchesSchema($this->getReport(false));
    }

    public static function databaseProvider(): array {
        return self::namedValues(['mysql', 'pgsql', 'sqlite', 'sqlite3', 'oci']);
    }

    #[DataProvider('cronProvider')]
    public function testCronMatchesSchema(string $cron): void {
        $this->appValues['backgroundjobs_mode'] = $cron;

        $this->assertReportMatchesSchema($this->getReport(false));
    }

    public static function cronProvider(): array {
        return self::namedValues(['ajax', 'webcron', 'cron']);
    }

    #[DataProvider('proxyProvider')]
    public function testProxySettingsMatchSchema(array $systemValues): void {
        $this->systemValues = array_merge($this->systemValues, $systemValues);

        $this->assertReportMatchesSchema($this->getReport(false));
    }

    public static function proxyProvider(): array {
        return [
            'no proxy'          => [[]],
            'proxy'             => [['proxy' => 'proxy.example:3128']],
            'ssl proxy'         => [['overwriteprotocol' => 'HTTPS']],
            'cli url subfolder' => [['overwrite.cli.url' => 'https://cloud.example/nextcloud']],
            'webroot subfolder' => [['overwritewebroot' => '/nextcloud']],
        ];
    }

    #[DataProvider('imageServiceProvider')]
    public function testImageServiceMatchesSchema(string $service, string $realHelper, bool $helperFails): void {
        $this->appValues['service/images'] = $service;
        $this->realImageHelper             = $realHelper;
        $this->imageHelperFails            = $helperFails;

        $this->assertReportMatchesSchema($this->getReport());
    }

    public static function imageServiceProvider(): array {
        $data = [];
        foreach(self::getHelperServiceConstants('IMAGES_') as $service) {
            if($service === HelperService::IMAGES_AUTO) continue;
            $data[ $service ]             = [$service, HelperService::IMAGES_GDLIB, false];
            $data[ 'auto as '.$service ] = [HelperService::IMAGES_AUTO, $service, false];
        }
        $data['auto with failing helper'] = [HelperService::IMAGES_AUTO, HelperService::IMAGES_IMAGICK, true];

        return $data;
    }

    #[DataProvider('serviceProvider')]
    public function testServiceMatchesSchema(string $setting, string $service): void {
        $this->appValues[ $setting ] = $service;

        $this->assertReportMatchesSchema($this->getReport());
    }

    public static function serviceProvider(): array {
        $data = [];
        foreach(
            [
                'service/favicon'  => 'FAVICON_',
                'service/preview'  => 'PREVIEW_',
                'service/security' => 'SECURITY_',
                'service/words'    => 'WORDS_',
            ] as $setting => $prefix
        ) {
            foreach(self::getHelperServiceConstants($prefix) as $service) {
                $data[ $setting.' '.$service ] = [$setting, $service];
            }
        }

        return $data;
    }

    #[DataProvider('serviceApiProvider')]
    public function testServiceApiSettingsMatchSchema(array $settings, bool $fails): void {
        $this->serviceSettings     = array_merge($this->serviceSettings, $settings);
        $this->serviceSettingsFail = $fails;

        $this->assertReportMatchesSchema($this->getReport());
    }

    public static function serviceApiProvider(): array {
        return [
            'custom apis'      => [
                [
                    'preview.api' => ['value' => 'secret-key', 'default' => ''],
                    'favicon.api' => ['value' => 'https://custom.example', 'default' => 'https://icons.example'],
                ],
                false
            ],
            'settings failing' => [[], true],
        ];
    }

    #[DataProvider('channelProvider')]
    public function testChannelMatchesSchema(string $channel): void {
        $this->channel = $channel;

        $this->assertReportMatchesSchema($this->getReport());
    }

    /**
     * All channels OCP\ServerVersion::getChannel() can return
     */
    public static function channelProvider(): array {
        return self::namedValues(['production', 'stable', 'beta', 'daily', 'enterprise', 'git']);
    }

    #[DataProvider('settingsProvider')]
    public function testSettingsMatchSchema(array $appValues): void {
        $this->appValues = array_merge($this->appValues, $appValues);

        $this->assertReportMatchesSchema($this->getReport());
    }

    public static function settingsProvider(): array {
        return [
            'defaults'             => [[]],
            'nightlies + handbook' => [['nightly/enabled' => '1', 'handbook/url' => 'https://handbook.example']],
            'performance 0'        => [['performance' => '0']],
            'performance 6'        => [['performance' => '6']],
            'performance too high' => [['performance' => '7']],
            'performance negative' => [['performance' => '-1']],
            'performance invalid'  => [['performance' => 'fast']],
        ];
    }

    #[DataProvider('autoBackupStatusProvider')]
    public function testAutoBackupStatusMatchesSchema(int $status): void {
        $this->appIntValues['auto-backup/status'] = $status;

        $this->assertReportMatchesSchema($this->getReport());
    }

    /**
     * Values written by OCA\Passwords\Migration\AutoBackupRestore
     */
    public static function autoBackupStatusProvider(): array {
        return self::namedValues([0, 1, 2, 3]);
    }

    #[DataProvider('appsProvider')]
    public function testAppsMatchSchema(array $apps): void {
        OC_App::$apps = $apps;

        $report = $this->getReport();

        $this->assertSame(
            ['guests', 'occweb', 'theming', 'passman', 'unsplash', 'impersonate', 'passwords_handbook', 'user_migration'],
            array_keys($report['apps'])
        );
        $this->assertReportMatchesSchema($report);
    }

    public static function appsProvider(): array {
        return [
            'no apps'        => [[]],
            'unrelated apps' => [[['id' => 'files', 'active' => true], ['id' => 'calendar', 'active' => false]]],
            'related apps'   => [
                [
                    ['id' => 'guests', 'active' => true],
                    ['id' => 'theming', 'active' => false],
                    ['id' => 'passwords_handbook', 'active' => true],
                    ['id' => 'user_migration', 'active' => false],
                ]
            ],
        ];
    }

    #[DataProvider('shareCountProvider')]
    public function testShareCountMatchesSchema(int $count): void {
        $this->shareCount = $count;

        $this->assertReportMatchesSchema($this->getReport());
    }

    public static function shareCountProvider(): array {
        return self::namedValues([0, 1, 10, 11, 25, 26, 50, 51, 100, 101, 500, 501, 5000]);
    }

    #[DataProvider('encryptionProvider')]
    public function testEncryptionMatchesSchema(array $revisionCounts): void {
        $this->revisionCounts = $revisionCounts;

        $this->assertReportMatchesSchema($this->getReport());
    }

    public static function encryptionProvider(): array {
        return [
            'single sse type'     => [['sse_type' => ['SSEv3r1' => 1], 'cse_type' => ['none' => 1]]],
            'all sse types'       => [
                [
                    'sse_type' => ['SSEv1r1' => 1, 'SSEv1r2' => 2, 'SSEv2r1' => 5, 'SSEv3r1' => 4, 'none' => 3],
                    'cse_type' => ['CSEv1r1' => 3, 'none' => 12],
                ]
            ],
            'client side only'    => [['sse_type' => ['none' => 4], 'cse_type' => ['CSEv1r1' => 4]]],
            'no revisions at all' => [['sse_type' => [], 'cse_type' => []]],
        ];
    }

    public function testEncryptionWithFailingMappersMatchesSchema(): void {
        $this->revisionMappersFail = true;

        $this->assertReportMatchesSchema($this->getReport());
    }

    /**
     * Encodes the report the same way the HTTP client does when sending it,
     * so that e.g. empty PHP arrays are checked as JSON arrays, not objects.
     */
    protected function assertReportMatchesSchema(array $report): void {
        $json = json_decode(json_encode($report, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $validator = new Validator();
        $validator->setMaxErrors(25);
        $result = $validator->validate($json, file_get_contents(self::SCHEMA_FILE));

        $errors = $result->isValid() ? []:(new ErrorFormatter())->format($result->error());
        $this->assertTrue(
            $result->isValid(),
            "Report does not match the schema:\n".
            json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).
            "\nReport:\n".
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function getReport(bool $enhanced = true): array {
        return $this->createHelper()->getReport($enhanced);
    }

    protected function createHelper(): ServerReportHelper {
        $config = $this->createMock(ConfigurationService::class);
        // The real method returns the default through a ?string return type without strict_types, which casts it
        $config->method('getAppValue')->willReturnCallback(
            fn(string $key, $default = null) => $this->appValues[ $key ] ?? ($default === null ? null:strval($default))
        );
        $config->method('getAppValueInt')->willReturnCallback(
            fn(string $key, int $default = 0) => $this->appIntValues[ $key ] ?? $default
        );
        $config->method('getSystemValue')->willReturnCallback(
            fn(string $key, $default = null) => $this->systemValues[ $key ] ?? $default
        );

        $autoImageHelper = $this->createMock(AutoImageHelper::class);
        $autoImageHelper->method('getRealImageHelperName')->willReturnCallback(
            fn() => $this->imageHelperFails ? throw new RuntimeException('No image helper'):$this->realImageHelper
        );
        $helperService = $this->createMock(HelperService::class);
        $helperService->method('getImageHelper')->willReturn($autoImageHelper);

        $serviceSettings = $this->createMock(ServiceSettingsHelper::class);
        $serviceSettings->method('get')->willReturnCallback(
            fn(string $key) => $this->serviceSettingsFail
                ? throw new ApiException('Unknown setting identifier')
                :$this->serviceSettings[ $key ]
        );

        $serverVersion = $this->createMock(ServerVersion::class);
        $serverVersion->method('getVersion')->willReturnCallback(fn() => $this->serverVersion);
        $serverVersion->method('getChannel')->willReturnCallback(fn() => $this->channel);

        $shareMapper = $this->createMock(ShareMapper::class);
        $shareMapper->method('findAll')->willReturnCallback(fn() => array_fill(0, $this->shareCount, null));

        return new ServerReportHelper(
            $shareMapper,
            $config,
            $helperService,
            $this->createMock(IClientService::class),
            $this->createRevisionMapper(TagRevisionMapper::class, 0),
            $serviceSettings,
            $serverVersion,
            $this->createRevisionMapper(FolderRevisionMapper::class, 1),
            $this->createRevisionMapper(PasswordRevisionMapper::class, 2)
        );
    }

    /**
     * Each mapper reports a third of the configured revisions, the remainder goes to the first mapper
     */
    protected function createRevisionMapper(string $class, int $index) {
        $mapper = $this->createMock($class);
        $mapper->method('findAllByField')->willReturnCallback(
            function (string $field, $value) use ($index) {
                if($this->revisionMappersFail) throw new Exception('Database error');

                $total = $this->revisionCounts[ $field ][ $value ] ?? 0;
                $count = intdiv($total, 3) + ($index === 0 ? $total % 3:0);

                return array_fill(0, $count, null);
            }
        );

        return $mapper;
    }

    protected static function getHelperServiceConstants(string $prefix): array {
        $constants = (new ReflectionClass(HelperService::class))->getConstants();

        return array_values(
            array_filter($constants, fn($name) => str_starts_with($name, $prefix), ARRAY_FILTER_USE_KEY)
        );
    }

    protected static function namedValues(array $values): array {
        $data = [];
        foreach($values as $value) {
            $data[ strval($value) ] = [$value];
        }

        return $data;
    }
}
