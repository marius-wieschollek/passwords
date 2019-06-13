<?php

namespace OCA\Passwords\Helper\Survey;

use Exception;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\AppSettings\ServiceSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\HelperService;

/**
 * Class ServerReportHelper
 *
 * @package OCA\Passwords\Helper\Survey
 */
class ServerReportHelper {

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
    }

    /**
     * @param bool $enhanced
     *
     * @return array
     */
    public function getReport(bool $enhanced = true): array {
        $report = [
            'versions'    => $this->getVersions(),
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
        return [
            'os'           => php_uname('s'),
            'architecture' => php_uname('m'),
            'bits'         => PHP_INT_SIZE * 8,
            'database'     => $this->config->getSystemValue('dbtype'),
            'cron'         => $this->config->getAppValue('backgroundjobs_mode', 'ajax', 'core')
        ];
    }

    /**
     * @return array
     */
    protected function getLegacyApi(): array {
        $checkpoint = $this->config->getAppValue('legacy_api_checkpoint', strtotime('last Monday'));
        $wasUsed    = $this->config->getAppValue('legacy_last_used', 0) > $checkpoint;
        $this->config->setAppValue('legacy_api_checkpoint', time());

        return [
            'enabled' => $this->config->getAppValue('legacy_api_enabled', true),
            'used'    => $wasUsed
        ];
    }

    /**
     * @return array
     */
    protected function getServices(): array {
        $images = HelperService::getImageHelperName($this->config->getAppValue('service/images', HelperService::IMAGES_IMAGICK));

        $previewApi = false;
        $faviconApi = false;
        try {
            $previewApi = $this->serviceSettings->get('preview.api')['value'] !== '';
            $faviconApi = $this->serviceSettings->get('favicon.api')['value'] !== '';
        } catch(ApiException $e) {
        }

        return [
            'images'     => $images,
            'favicons'   => $this->config->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT),
            'previews'   => $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT),
            'security'   => $this->config->getAppValue('service/security', HelperService::SECURITY_HIBP),
            'words'      => HelperService::getDefaultWordsHelperName(),
            'previewApi' => $previewApi,
            'faviconApi' => $faviconApi
        ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array {
        return [
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
        $data     = [
            'passman'  => [
                'installed' => false,
                'enabled'   => false,
            ],
            'unsplash' => [
                'installed' => false,
                'enabled'   => false,
            ]
        ];

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
            return -1;
        }
    }
}