<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OC_Defaults;
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
    protected $serverSettings = ['baseUrl'];

    /**
     * @var array
     */
    protected $themeSettings = ['color', 'text.color', 'background', 'logo', 'label', 'folder.icon'];

    /**
     * @var array
     */
    protected $userSettings
        = [
            'password/generator/strength' => 'integer',
            'password/generator/numbers'  => 'boolean',
            'password/generator/special'  => 'boolean',
            'password/label/default'      => 'integer'
        ];

    /**
     * @var array
     */
    protected $userDefaults
        = [
            'password/generator/strength' => 1,
            'password/generator/numbers'  => false,
            'password/generator/special'  => false,
            'password/default/label'      => 0
        ];

    /**
     * SettingsService constructor.
     *
     * @param ConfigurationService $config
     * @param OC_Defaults          $theming
     * @param IURLGenerator        $urlGenerator
     */
    public function __construct(ConfigurationService $config, OC_Defaults $theming, IURLGenerator $urlGenerator) {
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
                return $this->getUserSetting($subKey);
            case 'client':
                return $this->getClientSetting($subKey);
            case 'server':
                return $this->getServerSetting($subKey);
            case 'theme':
                return $this->getThemeSetting($subKey);
        }

        throw new ApiException('Invalid Scope', 400);
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws ApiException
     * @throws \OCP\PreConditionNotMetException
     */
    public function set(string $key, $value) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'user':
                $this->setUserSetting($subKey, $value);
                break;
            case 'client':
                $this->setClientSetting($subKey, $value);
                break;
            default:
                throw new ApiException('Invalid Scope', 400);
                break;
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     * @throws ApiException
     * @throws \OCP\PreConditionNotMetException
     */
    public function reset(string $key) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'user':
                return $this->resetUserSetting($subKey);
            case 'client':
                return $this->resetClientSetting($subKey);
        }

        throw new ApiException('Invalid Scope', 400);
    }

    /**
     * @param string|null $scope
     *
     * @return array
     * @throws ApiException
     */
    public function listSettings(string $scope = null): array {
        $settings = [];

        if($scope === null || $scope === 'server') {
            $settings['server'] = [];
            foreach($this->serverSettings as $setting) {
                $settings['server'][ $setting ] = $this->getServerSetting($setting);
            }
        }

        if($scope === null || $scope === 'theme') {
            $settings['theme'] = [];
            foreach($this->themeSettings as $setting) {
                $settings['theme'][ $setting ] = $this->getThemeSetting($setting);
            }
        }

        if($scope === null || $scope === 'user') {
            $settings['user'] = [];
            foreach(array_keys($this->userSettings) as $setting) {
                $setting                      = str_replace('/', '.', $setting);
                $settings['user'][ $setting ] = $this->getUserSetting($setting);
            }
        }

        if($scope === null || $scope === 'client') {
            $settings['client'] = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        }

        return $scope === null ? $settings:$settings[ $scope ];
    }

    /**
     * @param string $key
     *
     * @return null|string
     * @throws ApiException
     */
    protected function getUserSetting(string $key) {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $type    = $this->userSettings[ $key ];
            $default = $this->userDefaults[ $key ];
            $value   = $this->config->getUserValue($key, $default);

            return $this->castValue($type, $value);
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @param string $key
     *
     * @return null
     * @throws ApiException
     */
    protected function getClientSetting(string $key) {
        $data = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        if(isset($data[ $key ])) {
            return $data[ $key ];
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @param string $key
     *
     * @return null|string
     * @throws ApiException
     */
    protected function getServerSetting(string $key) {
        switch($key) {
            case 'baseUrl':
                return $this->urlGenerator->getBaseUrl();
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @param string $key
     *
     * @return null|string
     * @throws ApiException
     */
    protected function getThemeSetting(string $key) {
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

                return $this->urlGenerator->getAbsoluteURL(
                    $this->urlGenerator->imagePath('core', 'filetypes/folder.svg')
                );
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws \OCP\PreConditionNotMetException
     * @throws ApiException
     */
    protected function setUserSetting(string $key, $value): void {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $type  = $this->userSettings[ $key ];
            $value = $this->castValue($type, $value);
            if($type === 'boolean') $value = intval($value);
            $this->config->setUserValue($key, $value);
        } else {
            throw new ApiException('Invalid Key', 400);
        }
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws \OCP\PreConditionNotMetException
     * @throws ApiException
     */
    protected function setClientSetting(string $key, $value): void {
        if(strlen($key) > 16) {
            throw new ApiException('Key too long', 400);
        }
        if(strlen(strval($value)) > 36) {
            throw new ApiException('Value too long', 400);
        }

        $data         = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        $data[ $key ] = $value;
        $this->config->setUserValue('client/settings', json_encode($data));
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws ApiException
     */
    protected function resetUserSetting(string $key) {
        $key = str_replace('.', '/', $key);

        if(isset($this->userSettings[ $key ])) {
            $this->config->deleteUserValue($key);

            return $this->userDefaults[ $key ];
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @param string $key
     *
     * @return null
     * @throws \OCP\PreConditionNotMetException
     */
    protected function resetClientSetting(string $key) {
        $data = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        if(isset($data[ $key ])) {
            unset($data[ $key ]);
            $this->config->setUserValue('client/settings', json_encode($data));
        }

        return null;
    }

    /**
     * @param string $type
     * @param        $value
     *
     * @return bool|float|int|string
     */
    protected function castValue(string $type, $value) {
        if($type === 'integer') {
            return intval($value);
        } else if($type === 'float') {
            return floatval($value);
        } else if($type === 'boolean') {
            return boolval($value);
        }

        return strval($value);
    }
}