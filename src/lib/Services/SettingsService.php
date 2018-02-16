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
    protected $userSettings
        = [
            'password.generator.strength' => 'integer',
            'password.generator.numbers'  => 'boolean',
            'password.generator.special'  => 'boolean',
            'password.default.label'      => 'integer'
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
     */
    public function get(string $key) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'user':
                return $this->getUserValue($subKey);
            case 'client':
                return $this->getClientValue($subKey);
            case 'server':
                return $this->getServerValue($subKey);
            case 'theme':
                return $this->getThemeValue($subKey);
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return null|string
     */
    protected function getUserValue(string $key) {
        if(isset($this->userSettings, $key)) {
            $type  = $this->userSettings[ $key ];
            $key   = str_replace('.', '/', $key);
            $value = $this->config->getUserValue($key, null);

            return $this->castValue($type, $value);
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return null
     */
    protected function getClientValue(string $key) {
        $data = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        if(isset($data[ $key ])) {
            return $data[ $key ];
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

                return $this->urlGenerator->getAbsoluteURL(
                    $this->urlGenerator->imagePath('core', 'filetypes/folder.svg')
                );
        }

        return null;
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
                $this->setUserValue($subKey, $value);
                break;
            case 'client':
                $this->setClientValue($subKey, $value);
                break;
        }

        throw new ApiException('Invalid Key');
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws \OCP\PreConditionNotMetException
     * @throws ApiException
     */
    protected function setUserValue(string $key, $value): void {
        if(isset($this->userSettings, $key)) {
            $key = str_replace('.', '/', $key);
            $this->config->setUserValue($key, strval($value));
        }

        throw new ApiException('Invalid Key');
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws \OCP\PreConditionNotMetException
     * @throws ApiException
     */
    protected function setClientValue(string $key, $value): void {
        if(strlen($key) > 16) {
            throw new ApiException('Key too long');
        }
        if(strlen(strval($value)) > 36) {
            throw new ApiException('Value too long');
        }

        $data         = json_decode($this->config->getUserValue('client/settings', '{}'), true);
        $data[ $key ] = $value;
        $this->config->setUserValue('client/settings', json_encode($data));
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