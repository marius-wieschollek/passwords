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
     * @var array
     */
    protected $userSettings
        = [
            'password.generator.strength' => 'integer',
            'password.generator.numbers'  => 'boolean',
            'password.generator.special'  => 'boolean',
            'password.generator.smileys'  => 'boolean',
            'password.default.label'      => 'integer'
        ];

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
            case 'user':
                return $this->getUserValue($subKey);
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
    public function getUserValue(string $key) {
        if(isset($this->userSettings, $key)) {
            return $this->config->getUserValue('settings.'.$key, null);
        }

        return null;
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
            case 'color':
                return $this->theming->getColorPrimary();
            case 'text.color':
                return $this->theming->getTextColorPrimary();
            case 'background':
                if(method_exists($this->theming, 'getBackground')) {
                    $url = $this->theming->getBackground();
                } else {
                    list($version,) = explode('.', $this->config->getSystemValue('version'), 2);
                    $url = $this->urlGenerator->imagePath('core', 'background.'.($version === '12' ? 'jpg':'png'));
                }
                if($this->config->isAppEnabled('unsplash')) {
                    return 'https://source.unsplash.com/random/featured';
                }

                return $this->urlGenerator->getAbsoluteURL($url);
            case 'logo':
                return $this->urlGenerator->getAbsoluteURL($this->theming->getLogo());
            case 'label':
                return $this->theming->getEntity();
            case 'folder.icon':
                if($this->config->isAppEnabled('theming')) {
                    return $this->urlGenerator->linkToRouteAbsolute('theming.Icon.getThemedIcon', ['app' => 'core', 'image' => 'filetypes/folder.svg']);
                }

                return $this->urlGenerator->imagePath('core', 'filetypes/folder.svg');
        }

        return null;
    }
}