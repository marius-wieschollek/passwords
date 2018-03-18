<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OC_Defaults;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCP\IURLGenerator;

/**
 * Class ThemeSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ThemeSettingsHelper {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var OC_Defaults
     */
    protected $theming;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * ThemeSettingsHelper constructor.
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
     * @return null|string
     */
    public function get(string $key) {
        switch($key) {
            case 'color':
                return $this->theming->getColorPrimary();
            case 'text.color':
                return $this->theming->getTextColorPrimary();
            case 'background':
                return $this->getBackgroundImage();
            case 'logo':
                return $this->urlGenerator->getAbsoluteURL($this->theming->getLogo());
            case 'label':
                return $this->theming->getEntity();
            case 'app.icon':
                return $this->getAppIcon();
            case 'folder.icon':
                return $this->getFolderIcon();
        }

        return null;
    }

    /**
     * @return array
     */
    public function list(): array {
        return [
            'server.theme.color'       => $this->get('color'),
            'server.theme.text.color'  => $this->get('text.color'),
            'server.theme.background'  => $this->getBackgroundImage(),
            'server.theme.logo'        => $this->get('logo'),
            'server.theme.app.icon'    => $this->getAppIcon(),
            'server.theme.folder.icon' => $this->getFolderIcon()
        ];
    }

    /**
     * @return string
     */
    protected function getFolderIcon(): string {
        if($this->config->isAppEnabled('theming')) {
            return $this->urlGenerator->linkToRouteAbsolute('theming.Icon.getThemedIcon', ['app' => 'core', 'image' => 'filetypes/folder.svg']);
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath('core', 'filetypes/folder.svg')
        );
    }

    /**
     * @return string
     */
    protected function getAppIcon(): string {
        if($this->config->isAppEnabled('theming')) {
            return $this->urlGenerator->linkToRouteAbsolute('theming.Icon.getThemedIcon', ['app' => Application::APP_NAME, 'image' => 'app-themed.svg']);
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_NAME, 'app-themed.svg')
        );
    }

    /**
     * @return string
     */
    protected function getBackgroundImage(): string {
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
    }
}