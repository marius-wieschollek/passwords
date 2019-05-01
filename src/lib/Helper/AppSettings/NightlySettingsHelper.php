<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\AppSettings;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Fetcher\NightlyAppFetcher;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class NightlySettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class NightlySettingsHelper extends AbstractSettingsHelper {

    /**
     * @var NightlyAppFetcher
     */
    protected $nightlyAppFetcher;

    /**
     * @var
     */
    protected $scope = 'nightly';

    /**
     * @var array
     */
    protected $keys
        = [
            'enabled' => 'nightly/enabled'
        ];

    /**
     * @var array
     */
    protected $types
        = [
            'enabled' => 'boolean'
        ];

    /**
     * @var array
     */
    protected $defaults
        = [
            'enabled' => false
        ];

    /**
     * NightlySettingsHelper constructor.
     *
     * @param ConfigurationService $config
     * @param NightlyAppFetcher    $nightlyAppFetcher
     */
    public function __construct(ConfigurationService $config, NightlyAppFetcher $nightlyAppFetcher) {
        parent::__construct($config);
        $this->nightlyAppFetcher = $nightlyAppFetcher;
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @return array
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function set(string $key, $value): array {

        $result = parent::set($key, $value);
        if($key === 'enabled') $this->setNightlyStatus($result['value'] === true);

        return $result;
    }

    /**
     * @param $enabled
     */
    protected function setNightlyStatus($enabled): void {
        $nightlyApps = $this->config->getSystemValue('allowNightlyUpdates', []);

        if($enabled) {
            if(!in_array(Application::APP_NAME, $nightlyApps)) $nightlyApps[] = Application::APP_NAME;
            $this->config->setSystemValue('allowNightlyUpdates', $nightlyApps);
            $this->nightlyAppFetcher->get();
        } else {
            $index = array_search(Application::APP_NAME, $nightlyApps);
            if($index !== false) unset($nightlyApps[ $index ]);
            $this->config->setSystemValue('allowNightlyUpdates', $nightlyApps);
            $this->nightlyAppFetcher->clearDb();
        }
    }
}