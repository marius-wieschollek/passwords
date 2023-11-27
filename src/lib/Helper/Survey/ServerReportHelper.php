<?php

namespace OCA\Passwords\Helper\Survey;

use Exception;
use OC_App;
use OC_Util;
use OCA\Passwords\AppInfo\SystemRequirements;
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
use Throwable;

/**
 * Class ServerReportHelper
 *
 * @package OCA\Passwords\Helper\Survey
 */
class ServerReportHelper {

    const API_URL = 'https://statistics.passwordsapp.org/api.php';

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var ShareMapper
     */
    protected ShareMapper $shareMapper;

    /**
     * @var ServiceSettingsHelper
     */
    protected ServiceSettingsHelper $serviceSettings;

    /**
     * @var TagRevisionMapper
     */
    protected TagRevisionMapper $tagRevisionMapper;

    /**
     * @var FolderRevisionMapper
     */
    protected FolderRevisionMapper $folderRevisionMapper;

    /**
     * @var PasswordRevisionMapper
     */
    protected PasswordRevisionMapper $passwordRevisionMapper;

    /**
     * @var HelperService
     */
    protected HelperService $helperService;

    /**
     * @var IClientService
     */
    protected IClientService $httpClientService;

    /**
     * ServerReportHelper constructor.
     *
     * @param ShareMapper            $shareMapper
     * @param ConfigurationService   $config
     * @param HelperService          $helperService
     * @param IClientService         $httpClientService
     * @param TagRevisionMapper      $tagRevisionMapper
     * @param ServiceSettingsHelper  $serviceSettings
     * @param FolderRevisionMapper   $folderRevisionMapper
     * @param PasswordRevisionMapper $passwordRevisionMapper
     */
    public function __construct(
        ShareMapper            $shareMapper,
        ConfigurationService   $config,
        HelperService          $helperService,
        IClientService         $httpClientService,
        TagRevisionMapper      $tagRevisionMapper,
        ServiceSettingsHelper  $serviceSettings,
        FolderRevisionMapper   $folderRevisionMapper,
        PasswordRevisionMapper $passwordRevisionMapper
    ) {
        $this->config                 = $config;
        $this->shareMapper            = $shareMapper;
        $this->helperService          = $helperService;
        $this->serviceSettings        = $serviceSettings;
        $this->httpClientService      = $httpClientService;
        $this->tagRevisionMapper      = $tagRevisionMapper;
        $this->folderRevisionMapper   = $folderRevisionMapper;
        $this->passwordRevisionMapper = $passwordRevisionMapper;
    }

    /**
     * @param bool $enhanced
     */
    public function sendReport(bool $enhanced = true): void {
        if(!$this->hasData()) return;

        $currentWeek = date('W');
        if($this->config->getAppValue('survey/server/week', '') === $currentWeek) return;

        $report = $this->getReport($enhanced);
        try {
            $client = $this->httpClientService->newClient();
            $client->post(self::API_URL, ['json' => $report]);
            $this->config->setAppValue('survey/server/week', $currentWeek);
        } catch(Exception $e) {
        }
    }

    /**
     * @param bool $enhanced
     *
     * @return array
     */
    public function getReport(bool $enhanced = true): array {
        $report = [
            'version'     => $this->getVersions(),
            'environment' => $this->getEnvironment()
        ];

        if($enhanced) {
            $report['services']   = $this->getServices();
            $report['settings']   = $this->getSettings();
            $report['status']     = $this->getStatus();
            $report['apps']       = $this->getApps();
            $report['sharing']    = $this->getSharing();
            $report['encryption'] = $this->getEncryption();
        }

        return $report;
    }

    /**
     * @return array
     */
    protected function getVersions(): array {
        return [
            'server'  => $this->config->getSystemValue('version'),
            'app'     => $this->config->getAppValue('installed_version'),
            'lsr'     => SystemRequirements::APP_LSR,
            'php'     => $this->config->getAppValue('web/php/version/string', phpversion()),
            'cronPhp' => $this->config->getAppValue('cron/php/version/string', phpversion())
        ];
    }

    /**
     * @return array
     */
    protected function getEnvironment(): array {
        $subdirectory = (
            strlen(strval(parse_url($this->config->getSystemValue('overwrite.cli.url', ''), PHP_URL_PATH))) > 1 ||
            strlen(strval($this->config->getSystemValue('overwritewebroot', ''))) > 1
        );

        return [
            'os'           => php_uname('s'),
            'architecture' => php_uname('m'),
            'bits'         => PHP_INT_SIZE * 8,
            'database'     => $this->config->getSystemValue('dbtype'),
            'cron'         => $this->config->getAppValue('backgroundjobs_mode', 'ajax', 'core'),
            'proxy'        => !empty($this->config->getSystemValue('proxy', '')),
            'sslProxy'     => strtolower($this->config->getSystemValue('overwriteprotocol', '')) === 'https',
            'subdirectory' => $subdirectory,
        ];
    }

    /**
     * @return array
     */
    protected function getServices(): array {
        $images = $this->config->getAppValue('service/images', HelperService::IMAGES_AUTO);
        if($images === HelperService::IMAGES_AUTO) {
            try {
                /** @var AutoImageHelper $helper */
                $helper = $this->helperService->getImageHelper(HelperService::IMAGES_AUTO);
                $images = $helper->getRealImageHelperName();
            } catch(Throwable $e) {
                $images = HelperService::IMAGES_GDLIB;
            }
        }

        $previewApi = false;
        $faviconApi = false;
        try {
            $previewApi = $this->serviceSettings->get('preview.api')['value'] !== '';

            $faviconSetting = $this->serviceSettings->get('favicon.api');
            $faviconApi     = $faviconSetting['value'] !== $faviconSetting['default'];
        } catch(ApiException $e) {
        }

        return [
            'images'     => $images,
            'favicons'   => $this->config->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT),
            'previews'   => $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT),
            'security'   => $this->config->getAppValue('service/security', HelperService::SECURITY_HIBP),
            'words'      => $this->config->getAppValue('service/words', HelperService::WORDS_AUTO),
            'previewApi' => $previewApi,
            'faviconApi' => $faviconApi
        ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array {
        $performance = $this->config->getAppValue('performance', null);
        if($performance === null) $performance = in_array(php_uname('m'), ['amd64', 'x86_64']) ? 5:1;
        if($performance < 0 || $performance > 6) $performance = 2;

        return [
            'channel'     => OC_Util::getChannel(),
            'nightlies'   => $this->config->getAppValue('nightly/enabled', '0') === '1',
            'handbook'    => $this->config->getAppValue('handbook/url') !== null,
            'performance' => intval($performance)
        ];
    }

    /**
     * @return array
     */
    protected function getStatus(): array {
        $autoBackupRestored = $this->config->getAppValue('backup/update/restored', '0') === '1';

        return [
            'autoBackupRestored' => $autoBackupRestored
        ];
    }

    /**
     * @return array
     */
    protected function getApps(): array {
        $appClass = new OC_App();
        $apps     = $appClass->listAllApps();
        $data     = [];
        foreach(['guests', 'occweb', 'theming', 'passman', 'unsplash', 'impersonate', 'passwords_handbook'] as $app) {
            $data[ $app ] = [
                'installed' => false,
                'enabled'   => false
            ];
        }

        foreach($apps as $app) {
            if(isset($data[ $app['id'] ])) {
                $data[ $app['id'] ] = [
                    'installed' => true,
                    'enabled'   => $app['active']
                ];
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getSharing(): array {
        $count = count($this->shareMapper->findAll());

        if($count === 0) {
            $total = 0;
        } else if($count <= 10) {
            $total = 10;
        } else if($count <= 25) {
            $total = 25;
        } else if($count <= 50) {
            $total = 50;
        } else if($count <= 100) {
            $total = 100;
        } else if($count <= 500) {
            $total = 500;
        } else {
            $total = 501;
        }

        return ['shares' => $total];
    }

    /**
     * @return array
     */
    protected function getEncryption(): array {
        return [
            'sse' => $this->getSseEncryption(),
            'cse' => $this->getCseEncryption()
        ];
    }

    /**
     * @return array
     */
    protected function getSseEncryption(): array {
        $data = [];
        $best = [0, 'none'];

        foreach(['SSEv1r1', 'SSEv1r2', 'SSEv2r1', 'SSEv3r1', 'none'] as $encryption) {
            $count               = $this->countEntitiesByField($encryption);
            $data[ $encryption ] = $count > 0;
            if($count > $best[0]) $best = [$count, $encryption];
        }

        if($best[0] === 0) return [];

        $data['default'] = $best[1];

        return $data;
    }

    /**
     * @return array
     */
    protected function getCseEncryption(): array {
        $data = [];
        $best = [0, 'none'];

        foreach(['CSEv1r1', 'none'] as $encryption) {
            $count               = $this->countEntitiesByField($encryption, 'cse_type');
            $data[ $encryption ] = $count > 0;
            if($count > $best[0]) $best = [$count, $encryption];
        }

        if($best[0] === 0) return [];

        $data['default'] = $best[1];

        return $data;
    }

    /**
     * @param        $value
     * @param string $field
     *
     * @return int
     */
    protected function countEntitiesByField($value, $field = 'sse_type'): int {
        try {
            return count($this->tagRevisionMapper->findAllByField($field, $value))
                   + count($this->folderRevisionMapper->findAllByField($field, $value))
                   + count($this->passwordRevisionMapper->findAllByField($field, $value));
        } catch(Exception $e) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasData(): bool {
        return count($this->passwordRevisionMapper->findAll()) > 0;
    }
}