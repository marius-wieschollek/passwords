<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 14.02.18
 * Time: 23:51
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Theming\ThemingDefaults;
use OCP\IURLGenerator;

/**
 * Class SettingsService
 *
 * @package OCA\Passwords\Services
 */
class SettingsService {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var ThemingDefaults
     */
    protected $theming;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * SettingsService constructor.
     *
     * @param ConfigurationService $config
     * @param \OC_Defaults         $theming
     * @param IURLGenerator        $urlGenerator
     */
    public function __construct(ConfigurationService $config, \OC_Defaults $theming, IURLGenerator $urlGenerator) {
        $this->config       = $config;
        $this->theming      = $theming;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws ApiException
     */
    public function get(string $key) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'server':
                return $this->getServerValue($subKey);
            case 'theme':
                return $this->getThemeValue($subKey);
        }

        throw new ApiException('Invalid Scope');
    }

    /**
     * @param string $key
     *
     * @return null|string
     */
    protected function getServerValue(string $key) {
        switch($key) {
            case 'baseUrl':
                return $this->urlGenerator->getBaseUrl();
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return null|string
     */
    protected function getThemeValue(string $key) {
        switch($key) {
            case 'theme.color':
                return $this->theming->getColorPrimary();
            case 'theme.background':
                if(method_exists($this->theming, 'getBackground')) {
                    $url = $this->theming->getBackground();
                } else {
                    list($version,) = explode('.', $this->config->getSystemValue('version'), 2);
                    $url = $this->urlGenerator->imagePath('core', 'background.'.($version === '12' ? 'jpg':'png'));
                }
                if($this->config->getConfig()->getAppValue('unsplash', 'enabled', 'no') === 'yes') {
                    return 'https://source.unsplash.com/random/featured';
                }

                return $this->urlGenerator->getAbsoluteURL($url);
            case 'theme.logo':
                return $this->urlGenerator->getAbsoluteURL($this->theming->getLogo());
            case 'theme.label':
                return $this->theming->getEntity();
        }

        return null;
    }
}