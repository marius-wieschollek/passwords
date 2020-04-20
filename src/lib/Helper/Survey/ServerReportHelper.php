<?php

namespace OCA\Passwords\Helper\Survey;

use Exception;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;

/**
 * Class ServerReportHelper
 *
 * @package OCA\Passwords\Helper\Survey
 */
class ServerReportHelper {

    const API_URL = 'https://ncpw.mdns.eu/api.php';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var ShareMapper
     */
    protected $shareMapper;

    /**
     * @var ServiceSettingsHelper
     */
    protected $serviceSettings;

    /**
     * @var TagRevisionMapper
     */
    protected $tagRevisionMapper;

    /**
     * @var FolderRevisionMapper
     */
    protected $folderRevisionMapper;

    /**
     * @var PasswordRevisionMapper
     */
    protected $passwordRevisionMapper;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * ServerReportHelper constructor.
     *
     * @param ShareMapper            $shareMapper
     * @param ConfigurationService   $config
     * @param TagRevisionMapper      $tagRevisionMapper
     * @param ServiceSettingsHelper  $serviceSettings
     * @param FolderRevisionMapper   $folderRevisionMapper
     * @param PasswordRevisionMapper $passwordRevisionMapper
     */
    public function __construct(
        ShareMapper $shareMapper,
        RequestHelper $requestHelper,
        ConfigurationService $config,
        TagRevisionMapper $tagRevisionMapper,
        ServiceSettingsHelper $serviceSettings,
        FolderRevisionMapper $folderRevisionMapper,
        PasswordRevisionMapper $passwordRevisionMapper
    ) {
        $this->config                 = $config;
        $this->serviceSettings        = $serviceSettings;
        $this->passwordRevisionMapper = $passwordRevisionMapper;
        $this->folderRevisionMapper   = $folderRevisionMapper;
        $this->tagRevisionMapper      = $tagRevisionMapper;
        $this->shareMapper            = $shareMapper;
        $this->requestHelper          = $requestHelper;
    }

    /**
     * @param bool $enhanced
     */
    public function sendReport(bool $enhanced = true): void {
        if(!$this->hasData()) return;

        $currentWeek = date('W');
        if($this->config->getAppValue('survey/server/week', '') === $currentWeek) return;

        $report = $this->getReport($enhanced);
        $this->requestHelper->setJsonData($report);
        $this->requestHelper->send(self::API_URL);
        $this->config->setAppValue('survey/server/week', $currentWeek);
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
            $report['legacyApi']  = $this->getLegacyApi();
            $report['services']   = $this->getServices();
            $report['settings']   = $this->getSettings();
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
            'server' => $this->config->getSystemValue('version'),
            'app'    => $this->config->getAppValue('installed_version'),
            'php'    => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION
        ];
    }

    /**
     * @return array
     */
    protected function getEnvironment(): array {
        $subdirectory = (
            strlen(parse_url($this->config->getSystemValue('overwrite.cli.url', ''), PHP_URL_PATH)) > 1 ||
            strlen($this->config->getSystemValue('overwritewebroot', '')) > 1
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
    protected function getLegacyApi(): array {
        $checkpoint = $this->config->getAppValue('legacy_api_checkpoint', strtotime('last Monday'));
        $wasUsed    = $this->config->getAppValue('legacy_last_used', 0) > $checkpoint;
        $this->config->setAppValue('legacy_api_checkpoint', time());

        $status = $this->config->getAppValue('legacy_api_enabled', true);
        $report = -1;
        if($status === false) {
            $report = 0;
        } else if($status === true) {
            $report = 1;
        } else if($status == false) {
            $report = 2;
        } else if($status == true) {
            $report = 3;
        }

        return [
            'enabled' => $report,
            'used'    => $wasUsed
        ];
    }

    /**
     * @return array
     */
    protected function getServices(): array {
        $words  = $this->config->getAppValue('service/words', HelperService::getDefaultWordsHelperName());
        $images = HelperService::getImageHelperName($this->config->getAppValue('service/images', HelperService::IMAGES_IMAGICK));

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
            'words'      => $words,
            'previewApi' => $previewApi,
            'faviconApi' => $faviconApi
        ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array {
        return [
            'channel'   => \OC_Util::getChannel(),
            'nightlies' => $this->config->getAppValue('nightly/enabled', '0') === '1',
            'handbook'  => $this->config->getAppValue('handbook/url') !== null,
        ];
    }

    /**
     * @return array
     */
    protected function getApps(): array {
        $appClass = new \OC_App();
        $apps     = $appClass->listAllApps();
        $data     = [];
        foreach(['guests', 'occweb', 'theming', 'passman', 'unsplash', 'impersonate'] as $app) {
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

        foreach(['SSEv1r1', 'SSEv1r2', 'SSEv2r1', 'none'] as $encryption) {
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